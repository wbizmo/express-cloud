<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

final readonly class LisaBusinessContext
{
    /**
     * Build the authorised-scope business snapshot used by Lisa when
     * answering questions in the chat assistant.
     *
     * @return array{
     *     today: array{sales_count: int, sales_total_kobo: int, unpaid_total_kobo: int},
     *     inventory: array{low_stock_rows: int, zero_stock_rows: int},
     *     procurement: array{open_purchase_orders: int},
     *     accounting: array{month_revenue_kobo: int, month_expense_kobo: int, cash_and_bank_kobo: int},
     *     staffPerformance: list<array{name: string, sales_count: int, sales_total_kobo: int}>,
     * }
     */
    public function for(Account $account): array
    {
        $branchIds = $this->authorisedBranchIds($account);

        return [
            'today' => $this->todaySales($branchIds),
            'inventory' => $this->inventory($branchIds),
            'procurement' => $this->procurement($branchIds),
            'accounting' => $this->accounting($branchIds),
            'staffPerformance' => $this->staffPerformance($branchIds),
        ];
    }

    /** @return list<string>|null null means the account is authorised for every branch */
    private function authorisedBranchIds(Account $account): ?array
    {
        if ($account->is_allowed_all_branches) {
            return null;
        }

        return $account->branches()->pluck('branches.id')->all();
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{sales_count: int, sales_total_kobo: int, unpaid_total_kobo: int}
     */
    private function todaySales(?array $branchIds): array
    {
        $today = today()->toDateString();

        $sales = DB::table('sales')
            ->whereDate('sale_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds));

        return [
            'sales_count' => (int) (clone $sales)->count(),
            'sales_total_kobo' => (int) (clone $sales)->sum('grand_total_kobo'),
            'unpaid_total_kobo' => (int) (clone $sales)
                ->selectRaw('COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS unpaid_total')
                ->value('unpaid_total'),
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{low_stock_rows: int, zero_stock_rows: int}
     */
    private function inventory(?array $branchIds): array
    {
        $stock = DB::table('product_branch_stock')
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds));

        return [
            'low_stock_rows' => (int) (clone $stock)
                ->whereColumn('quantity_milliunits', '<=', 'minimum_stock_milliunits')
                ->count(),
            'zero_stock_rows' => (int) (clone $stock)
                ->where('quantity_milliunits', '<=', 0)
                ->count(),
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{open_purchase_orders: int}
     */
    private function procurement(?array $branchIds): array
    {
        $openPurchaseOrders = (int) DB::table('purchase_orders')
            ->whereNotIn('status', ['received', 'cancelled'])
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->count();

        return [
            'open_purchase_orders' => $openPurchaseOrders,
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{month_revenue_kobo: int, month_expense_kobo: int, cash_and_bank_kobo: int}
     */
    private function accounting(?array $branchIds): array
    {
        $monthStart = today()->startOfMonth()->toDateString();
        $monthEnd = today()->toDateString();

        $movement = static function (string $type) use ($branchIds, $monthStart, $monthEnd): int {
            return (int) DB::table('journal_lines')
                ->join(
                    'journal_entries',
                    'journal_entries.id',
                    '=',
                    'journal_lines.journal_entry_id',
                )
                ->join(
                    'ledger_accounts',
                    'ledger_accounts.id',
                    '=',
                    'journal_lines.ledger_account_id',
                )
                ->where('ledger_accounts.type', $type)
                ->where('journal_entries.status', 'posted')
                ->whereBetween('journal_entries.entry_date', [$monthStart, $monthEnd])
                ->when(
                    $branchIds !== null,
                    fn ($q) => $q->whereIn('journal_entries.branch_id', $branchIds),
                )
                ->selectRaw(
                    $type === 'revenue'
                        ? 'COALESCE(SUM(journal_lines.credit_kobo - journal_lines.debit_kobo), 0) AS total'
                        : 'COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS total',
                )
                ->value('total');
        };

        $cashAndBank = (int) DB::table('journal_lines')
            ->join(
                'ledger_accounts',
                'ledger_accounts.id',
                '=',
                'journal_lines.ledger_account_id',
            )
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->whereIn('ledger_accounts.code', ['1000', '1010', '1020'])
            ->where('journal_entries.status', 'posted')
            ->when(
                $branchIds !== null,
                fn ($q) => $q->whereIn('journal_entries.branch_id', $branchIds),
            )
            ->selectRaw(
                'COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS total',
            )
            ->value('total');

        return [
            'month_revenue_kobo' => $movement('revenue'),
            'month_expense_kobo' => $movement('expense'),
            'cash_and_bank_kobo' => $cashAndBank,
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return list<array{name: string, sales_count: int, sales_total_kobo: int}>
     */
    private function staffPerformance(?array $branchIds): array
    {
        $monthStart = today()->startOfMonth()->toDateString();

        return DB::table('sales')
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'sales.sold_by_account_id',
            )
            ->where('sales.sale_date', '>=', $monthStart)
            ->whereNotIn('sales.status', ['cancelled'])
            ->when(
                $branchIds !== null,
                fn ($q) => $q->whereIn('sales.branch_id', $branchIds),
            )
            ->groupBy('accounts.id', 'accounts.first_name', 'accounts.last_name')
            ->orderByDesc('sales_total_kobo')
            ->limit(10)
            ->selectRaw(
                "CONCAT(accounts.first_name, ' ', accounts.last_name) AS name",
            )
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('SUM(sales.grand_total_kobo) AS sales_total_kobo')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'sales_count' => (int) $row->sales_count,
                'sales_total_kobo' => (int) $row->sales_total_kobo,
            ])
            ->all();
    }
}
