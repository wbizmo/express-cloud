<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProductActivityController
{
    public function __invoke(Product $product): View
    {
        $auditTable = Schema::hasTable('activity_logs')
            ? 'activity_logs'
            : 'audit_logs';

        return view('admin.activity.product', [
            'product' => $product,
            'stockMovements' => DB::table('stock_movements')
                ->leftJoin(
                    'branches',
                    'branches.id',
                    '=',
                    'stock_movements.branch_id',
                )
                ->leftJoin(
                    'accounts',
                    'accounts.id',
                    '=',
                    'stock_movements.account_id',
                )
                ->where(
                    'stock_movements.product_id',
                    $product->getKey(),
                )
                ->orderByDesc('stock_movements.occurred_at')
                ->limit(200)
                ->select([
                    'stock_movements.*',
                    'branches.name AS branch_name',
                    'accounts.first_name',
                    'accounts.last_name',
                ])
                ->get(),
            'activity' => DB::table($auditTable)
                ->where('entity_type', 'product')
                ->where('entity_id', $product->getKey())
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
        ]);
    }
}
