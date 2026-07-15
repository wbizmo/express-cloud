<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StaffPerformanceReport
{
    /** @return Collection<int, \stdClass> */
    public function run(
        string $from,
        string $to,
        ?string $branchId,
    ): Collection {
        return DB::table('accounts')
            ->leftJoin(
                'sales',
                static function ($join) use (
                    $from,
                    $to,
                    $branchId,
                ): void {
                    $join->on(
                        'sales.sold_by_account_id',
                        '=',
                        'accounts.id',
                    )
                        ->whereBetween(
                            'sales.sale_date',
                            [$from, $to],
                        )
                        ->whereIn(
                            'sales.sale_type',
                            ['invoice', 'pos'],
                        )
                        ->whereNotIn(
                            'sales.status',
                            ['cancelled'],
                        );

                    if ($branchId !== null) {
                        $join->where(
                            'sales.branch_id',
                            '=',
                            $branchId,
                        );
                    }
                },
            )
            ->leftJoin(
                'sale_items',
                'sale_items.sale_id',
                '=',
                'sales.id',
            )
            ->where('accounts.account_type', 'staff')
            ->groupBy(
                'accounts.id',
                'accounts.first_name',
                'accounts.last_name',
            )
            ->orderByDesc('revenue_kobo')
            ->select([
                'accounts.id AS account_id',
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
            ->get();
    }
}
