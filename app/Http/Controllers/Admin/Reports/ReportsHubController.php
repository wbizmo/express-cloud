<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportsHubController
{
    public function __invoke(Request $request): View
    {
        $from = $request->date('from')?->toDateString()
            ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString()
            ?? today()->toDateString();
        $branchId = $request->filled('branch')
            ? $request->string('branch')->toString()
            : null;

        $sales = DB::table('sales')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->leftJoin(
                'accounts',
                'accounts.id',
                '=',
                'sales.sold_by_account_id',
            )
            ->whereBetween('sales.sale_date', [$from, $to])
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'sales.branch_id',
                    $branchId,
                ),
            )
            ->orderByDesc('sales.sale_date')
            ->select([
                'sales.sale_code',
                'sales.sale_type',
                'sales.sale_date',
                'sales.status',
                'sales.grand_total_kobo',
                'branches.name AS branch_name',
                'accounts.first_name',
                'accounts.last_name',
            ])
            ->paginate(config('pagination.default', 10))
            ->withQueryString();

        $topItems = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNotIn('sales.status', ['cancelled'])
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'sales.branch_id',
                    $branchId,
                ),
            )
            ->groupBy(
                'sale_items.product_id',
                'sale_items.product_name_snapshot',
            )
            ->orderByDesc('units_milliunits')
            ->limit(20)
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
            ->get();

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
                'products.name AS product_name',
                'products.sku',
                'branches.name AS branch_name',
                'low_stock_alerts.quantity_milliunits',
                'low_stock_alerts.minimum_stock_milliunits',
            ])
            ->get();

        return view('admin.reports.hub', [
            'from' => $from,
            'to' => $to,
            'selectedBranch' => $branchId,
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'sales' => $sales,
            'topItems' => $topItems,
            'lowStock' => $lowStock,
        ]);
    }
}
