<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class StaffDashboardData
{
    public function __construct(private AuthorizationService $authorization) {}

    /** @return array<string, mixed> */
    public function for(Account $account): array
    {
        $sales = DB::table('sales')
            ->where('sold_by_account_id', $account->getKey())
            ->whereNotIn('status', ['cancelled']);

        $today = today()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $recentSales = (clone $sales)
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'reference', 'sale_type', 'status', 'sale_date', 'grand_total_kobo', 'balance_due_kobo']);

        $branchIds = $account->is_allowed_all_branches
            ? []
            : $account->branches()->pluck('branches.id')->all();

        $lowStockCount = 0;
        if ($this->authorization->hasAnyPermission($account, ['inventory.view', 'reports.low-stock'])) {
            $stock = DB::table('product_branch_stocks')
                ->whereColumn('quantity_milliunits', '<=', 'reorder_level_milliunits');
            if (! $account->is_allowed_all_branches) {
                $stock->whereIn('branch_id', $branchIds);
            }
            $lowStockCount = $stock->count();
        }

        return [
            'todaySalesCount' => (clone $sales)->whereDate('sale_date', $today)->count(),
            'todayRevenueKobo' => (int) (clone $sales)->whereDate('sale_date', $today)->sum('grand_total_kobo'),
            'monthRevenueKobo' => (int) (clone $sales)->whereBetween('sale_date', [$monthStart, $today])->sum('grand_total_kobo'),
            'outstandingKobo' => (int) (clone $sales)->where('balance_due_kobo', '>', 0)->sum('balance_due_kobo'),
            'recentSales' => $recentSales,
            'lowStockCount' => $lowStockCount,
            'permissions' => $this->authorization->permissionSlugs($account),
        ];
    }
}
