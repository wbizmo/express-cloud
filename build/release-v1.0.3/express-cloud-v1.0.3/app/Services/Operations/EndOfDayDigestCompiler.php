<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Illuminate\Support\Facades\DB;

final class EndOfDayDigestCompiler
{
    /**
     * @return array{
     *   business_date:string,
     *   total_sales_kobo:int,
     *   sales_by_branch:list<array<string, mixed>>,
     *   payments:list<array<string, mixed>>,
     *   top_items:list<array<string, mixed>>,
     *   staff:list<array<string, mixed>>,
     *   low_stock:list<array<string, mixed>>
     * }
     */
    public function compile(string $businessDate): array
    {
        $salesBase = DB::table('sales')
            ->whereDate('sale_date', $businessDate)
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled']);

        $totalSales = (int) (clone $salesBase)->sum(
            'grand_total_kobo',
        );

        $salesByBranch = DB::table('sales')
            ->join(
                'branches',
                'branches.id',
                '=',
                'sales.branch_id',
            )
            ->whereDate('sales.sale_date', $businessDate)
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_kobo')
            ->select([
                'branches.id',
                'branches.name',
            ])
            ->selectRaw(
                'SUM(sales.grand_total_kobo) AS total_kobo',
            )
            ->get()
            ->map(
                static fn (object $row): array => [
                    'branch_id' => (string) $row->id,
                    'branch_name' => (string) $row->name,
                    'total_kobo' => (int) $row->total_kobo,
                ],
            )
            ->values()
            ->all();

        $payments = DB::table('payments')
            ->join(
                'payment_methods',
                'payment_methods.id',
                '=',
                'payments.payment_method_id',
            )
            ->whereDate('payments.paid_at', $businessDate)
            ->groupBy(
                'payment_methods.id',
                'payment_methods.name',
            )
            ->orderByDesc('total_kobo')
            ->select([
                'payment_methods.id',
                'payment_methods.name',
            ])
            ->selectRaw('SUM(payments.amount_kobo) AS total_kobo')
            ->get()
            ->map(
                static fn (object $row): array => [
                    'method_id' => (string) $row->id,
                    'method_name' => (string) $row->name,
                    'total_kobo' => (int) $row->total_kobo,
                ],
            )
            ->values()
            ->all();

        $topItems = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.sale_date', $businessDate)
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->groupBy(
                'sale_items.product_id',
                'sale_items.product_name_snapshot',
            )
            ->orderByDesc('units_milliunits')
            ->limit(5)
            ->select([
                'sale_items.product_id',
                'sale_items.product_name_snapshot',
            ])
            ->selectRaw(
                'SUM(sale_items.quantity_milliunits) AS units_milliunits',
            )
            ->selectRaw(
                'SUM(sale_items.line_total_kobo) AS revenue_kobo',
            )
            ->get()
            ->map(
                static fn (object $row): array => [
                    'product_id' => (string) $row->product_id,
                    'product_name' => (string) $row->product_name_snapshot,
                    'units_milliunits' => (int) $row->units_milliunits,
                    'revenue_kobo' => (int) $row->revenue_kobo,
                ],
            )
            ->values()
            ->all();

        $staff = DB::table('accounts')
            ->join(
                'sales',
                'sales.sold_by_account_id',
                '=',
                'accounts.id',
            )
            ->leftJoin(
                'sale_items',
                'sale_items.sale_id',
                '=',
                'sales.id',
            )
            ->whereDate('sales.sale_date', $businessDate)
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->groupBy(
                'accounts.id',
                'accounts.first_name',
                'accounts.last_name',
            )
            ->orderByDesc('revenue_kobo')
            ->select([
                'accounts.id',
                'accounts.first_name',
                'accounts.last_name',
            ])
            ->selectRaw(
                'COUNT(DISTINCT sales.id) AS sales_count',
            )
            ->selectRaw(
                'COALESCE(SUM(DISTINCT sales.grand_total_kobo), 0) AS revenue_kobo',
            )
            ->selectRaw(
                'COALESCE(SUM(sale_items.quantity_milliunits), 0) AS units_milliunits',
            )
            ->selectRaw(
                'COUNT(DISTINCT sales.customer_id) AS customers_served',
            )
            ->get()
            ->map(
                static fn (object $row): array => [
                    'account_id' => (string) $row->id,
                    'name' => trim(
                        (string) $row->first_name
                        .' '
                        .(string) $row->last_name,
                    ),
                    'sales_count' => (int) $row->sales_count,
                    'revenue_kobo' => (int) $row->revenue_kobo,
                    'units_milliunits' => (int) $row->units_milliunits,
                    'customers_served' => (int) $row->customers_served,
                ],
            )
            ->values()
            ->all();

        $lowStock = DB::table('low_stock_alerts')
            ->join(
                'products',
                'products.id',
                '=',
                'low_stock_alerts.product_id',
            )
            ->join(
                'branches',
                'branches.id',
                '=',
                'low_stock_alerts.branch_id',
            )
            ->whereNull('low_stock_alerts.resolved_at')
            ->orderBy('branches.name')
            ->orderBy('products.name')
            ->select([
                'products.id AS product_id',
                'products.name AS product_name',
                'products.sku',
                'branches.id AS branch_id',
                'branches.name AS branch_name',
                'low_stock_alerts.quantity_milliunits',
                'low_stock_alerts.minimum_stock_milliunits',
            ])
            ->get()
            ->map(
                static fn (object $row): array => [
                    'product_id' => (string) $row->product_id,
                    'product_name' => (string) $row->product_name,
                    'sku' => (string) $row->sku,
                    'branch_id' => (string) $row->branch_id,
                    'branch_name' => (string) $row->branch_name,
                    'quantity_milliunits' => (int) $row->quantity_milliunits,
                    'minimum_stock_milliunits' => (int) $row->minimum_stock_milliunits,
                ],
            )
            ->values()
            ->all();

        return [
            'business_date' => $businessDate,
            'total_sales_kobo' => $totalSales,
            'sales_by_branch' => $salesByBranch,
            'payments' => $payments,
            'top_items' => $topItems,
            'staff' => $staff,
            'low_stock' => $lowStock,
        ];
    }
}
