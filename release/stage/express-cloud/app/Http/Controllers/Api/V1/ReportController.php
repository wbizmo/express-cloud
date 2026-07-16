<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ReportController
{
    public function salesByBranch(): JsonResponse
    {
        $rows = DB::table('sales')
            ->join(
                'branches',
                'branches.id',
                '=',
                'sales.branch_id',
            )
            ->whereNotIn('sales.status', ['cancelled'])
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_kobo')
            ->select([
                'branches.id',
                'branches.name',
            ])
            ->selectRaw(
                'SUM(sales.grand_total_kobo) AS total_kobo',
            )
            ->get();

        return ApiResponse::success($rows);
    }

    public function lowStock(): JsonResponse
    {
        $rows = DB::table('low_stock_alerts')
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
            ->get([
                'products.id AS product_id',
                'products.name AS product_name',
                'products.sku',
                'branches.id AS branch_id',
                'branches.name AS branch_name',
                'low_stock_alerts.quantity_milliunits',
                'low_stock_alerts.minimum_stock_milliunits',
            ]);

        return ApiResponse::success($rows);
    }
}
