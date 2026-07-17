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
            ->get([
                'id', 'sale_code', 'sale_type', 'status', 'sale_date',
                'grand_total_kobo', 'paid_amount_kobo',
            ])
            ->map(function (object $sale): object {
                $sale->balance_due_kobo = max(
                    0,
                    (int) $sale->grand_total_kobo - (int) $sale->paid_amount_kobo,
                );

                return $sale;
            });

        $outstandingKobo = (int) (clone $sales)
            ->selectRaw('COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS outstanding_total')
            ->value('outstanding_total');

        return [
            'todaySalesCount' => (clone $sales)->whereDate('sale_date', $today)->count(),
            'todayRevenueKobo' => (int) (clone $sales)->whereDate('sale_date', $today)->sum('grand_total_kobo'),
            'monthRevenueKobo' => (int) (clone $sales)->whereBetween('sale_date', [$monthStart, $today])->sum('grand_total_kobo'),
            'outstandingKobo' => $outstandingKobo,
            'recentSales' => $recentSales,
            'permissions' => $this->authorization->permissionSlugs($account),
        ];
    }
}
