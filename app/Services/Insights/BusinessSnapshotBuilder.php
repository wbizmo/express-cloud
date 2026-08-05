<?php

declare(strict_types=1);

namespace App\Services\Insights;

use Illuminate\Support\Facades\DB;

final class BusinessSnapshotBuilder
{
    /**
     * @param  list<string>|null  $branchIds
     * @return array{payload:array<string,mixed>,evidence:list<array<string,mixed>>}
     */
    public function build(?array $branchIds, string $periodStart, string $periodEnd): array
    {
        $today = $periodEnd;
        $sales = DB::table('sales')
            ->whereDate('sale_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds));

        $stock = DB::table('product_branch_stock')
            ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds));

        $openPurchaseOrders = DB::table('purchase_orders')
            ->whereNotIn('status', ['received', 'cancelled'])
            ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds));

        $ledger = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$periodStart, $periodEnd])
            ->when($branchIds !== null, static fn ($query) => $query->whereIn('journal_entries.branch_id', $branchIds));

        $todayPayload = [
            'sales_count' => (int) (clone $sales)->count(),
            'sales_total_kobo' => (int) (clone $sales)->sum('grand_total_kobo'),
            'unpaid_total_kobo' => (int) (clone $sales)
                ->selectRaw('COALESCE(SUM(CASE WHEN grand_total_kobo > paid_amount_kobo THEN grand_total_kobo - paid_amount_kobo ELSE 0 END), 0) AS unpaid_total')
                ->value('unpaid_total'),
        ];
        $inventoryPayload = [
            'low_stock_rows' => (int) (clone $stock)
                ->whereColumn('quantity_milliunits', '<=', 'minimum_stock_milliunits')->count(),
            'zero_stock_rows' => (int) (clone $stock)->where('quantity_milliunits', '<=', 0)->count(),
        ];
        $procurementPayload = ['open_purchase_orders' => (int) $openPurchaseOrders->count()];

        $revenue = (int) (clone $ledger)
            ->where('ledger_accounts.type', 'revenue')
            ->selectRaw('COALESCE(SUM(journal_lines.credit_kobo - journal_lines.debit_kobo), 0) AS total')
            ->value('total');
        $expense = (int) (clone $ledger)
            ->where('ledger_accounts.type', 'expense')
            ->selectRaw('COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS total')
            ->value('total');
        $cashAndBank = (int) (clone $ledger)
            ->whereIn('ledger_accounts.code', ['1000', '1010', '1020'])
            ->selectRaw('COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS total')
            ->value('total');
        $accountingPayload = [
            'month_revenue_kobo' => $revenue,
            'month_expense_kobo' => $expense,
            'cash_and_bank_kobo' => $cashAndBank,
        ];

        $staffPerformance = DB::table('sales')
            ->join('accounts', 'accounts.id', '=', 'sales.sold_by_account_id')
            ->whereBetween('sales.sale_date', [$periodStart, $periodEnd])
            ->whereNotIn('sales.status', ['cancelled'])
            ->when($branchIds !== null, static fn ($query) => $query->whereIn('sales.branch_id', $branchIds))
            ->groupBy('accounts.id', 'accounts.first_name', 'accounts.last_name')
            ->orderByDesc('sales_total_kobo')
            ->limit(10)
            ->addSelect(['accounts.first_name', 'accounts.last_name'])
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('SUM(sales.grand_total_kobo) AS sales_total_kobo')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => trim((string) $row->first_name.' '.(string) $row->last_name),
                'sales_count' => (int) $row->sales_count,
                'sales_total_kobo' => (int) $row->sales_total_kobo,
            ])->all();

        $payload = [
            'period' => ['start' => $periodStart, 'end' => $periodEnd],
            'today' => $todayPayload,
            'inventory' => $inventoryPayload,
            'procurement' => $procurementPayload,
            'accounting' => $accountingPayload,
            'staffPerformance' => $staffPerformance,
        ];

        $evidence = [];
        foreach ([
            ['today', 'sales', $todayPayload],
            ['inventory', 'product_branch_stock', $inventoryPayload],
            ['procurement', 'purchase_orders', $procurementPayload],
            ['accounting', 'journal_lines', $accountingPayload],
            ['staffPerformance', 'sales', $staffPerformance],
        ] as [$metricKey, $sourceTable, $value]) {
            $evidence[] = [
                'metric_key' => $metricKey,
                'source_table' => $sourceTable,
                'source_query_hash' => hash('sha256', $metricKey.'|'.$periodStart.'|'.$periodEnd.'|'.json_encode($branchIds)),
                'value_payload' => ['value' => $value],
                'observed_at' => now(),
            ];
        }

        return ['payload' => $payload, 'evidence' => $evidence];
    }
}
