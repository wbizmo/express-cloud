<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryValuationSnapshot;
use App\Models\WarehouseStockBalance;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class InventoryValuationService
{
    public function snapshot(CarbonInterface $date): int
    {
        $captured = 0;

        DB::transaction(function () use ($date, &$captured): void {
            WarehouseStockBalance::query()
                ->orderBy('id')
                ->chunkById(200, function ($balances) use ($date, &$captured): void {
                    foreach ($balances as $balance) {
                        /** @var WarehouseStockBalance $balance */
                        $identity = hash('sha256', implode('|', [
                            $date->toDateString(),
                            (string) $balance->warehouse_id,
                            (string) $balance->product_id,
                            (string) ($balance->product_variant_id ?? ''),
                            (string) ($balance->inventory_batch_id ?? ''),
                            (string) $balance->condition,
                        ]));

                        InventoryValuationSnapshot::query()->updateOrCreate(
                            ['identity_hash' => $identity],
                            [
                                'snapshot_date' => $date->toDateString(),
                                'warehouse_id' => $balance->warehouse_id,
                                'product_id' => $balance->product_id,
                                'product_variant_id' => $balance->product_variant_id,
                                'inventory_batch_id' => $balance->inventory_batch_id,
                                'condition' => $balance->condition,
                                'quantity_milliunits' => $balance->quantity_milliunits,
                                'weighted_average_cost_kobo' => $balance->weighted_average_cost_kobo,
                                'inventory_value_kobo' => $balance->inventory_value_kobo,
                                'captured_at' => now(),
                            ],
                        );
                        $captured++;
                    }
                });
        }, 3);

        return $captured;
    }

    /** @return array{balance_rows:int,negative_rows:int,reserved_overruns:int,total_value_kobo:int} */
    public function audit(): array
    {
        return [
            'balance_rows' => WarehouseStockBalance::query()->count(),
            'negative_rows' => WarehouseStockBalance::query()
                ->where('quantity_milliunits', '<', 0)->count(),
            'reserved_overruns' => WarehouseStockBalance::query()
                ->whereColumn('reserved_milliunits', '>', 'quantity_milliunits')->count(),
            'total_value_kobo' => (int) WarehouseStockBalance::query()
                ->sum('inventory_value_kobo'),
        ];
    }
}
