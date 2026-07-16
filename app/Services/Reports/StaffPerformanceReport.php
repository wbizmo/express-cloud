<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StaffPerformanceReport
{
    /** @return Collection<int, \stdClass> */
    public function run(string $from, string $to, ?string $branchId): Collection
    {
        $sales = DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->when($branchId !== null, static fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('sold_by_account_id')
            ->selectRaw('sold_by_account_id AS account_id')
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('COALESCE(SUM(grand_total_kobo), 0) AS revenue_kobo')
            ->selectRaw('COUNT(DISTINCT customer_id) AS customers_served');

        $units = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->when($branchId !== null, static fn ($query) => $query->where('sales.branch_id', $branchId))
            ->groupBy('sales.sold_by_account_id')
            ->selectRaw('sales.sold_by_account_id AS account_id')
            ->selectRaw('COALESCE(SUM(sale_items.quantity_milliunits), 0) AS units_milliunits');

        return DB::table('accounts')
            ->joinSub($sales, 'staff_sales', static fn ($join) => $join->on('staff_sales.account_id', '=', 'accounts.id'))
            ->leftJoinSub($units, 'staff_units', static fn ($join) => $join->on('staff_units.account_id', '=', 'accounts.id'))
            ->where('accounts.status', 'active')
            ->orderByDesc('staff_sales.revenue_kobo')
            ->select([
                'accounts.id AS account_id',
                'accounts.first_name',
                'accounts.last_name',
                'staff_sales.sales_count',
                'staff_sales.revenue_kobo',
                'staff_sales.customers_served',
            ])
            ->selectRaw('COALESCE(staff_units.units_milliunits, 0) AS units_milliunits')
            ->get();
    }
}
