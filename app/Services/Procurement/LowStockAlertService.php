<?php

declare(strict_types=1);

namespace App\Services\Procurement;

use App\Models\LowStockAlert;
use App\Models\ProductBranchStock;

final class LowStockAlertService
{
    public function refresh(ProductBranchStock $stock): void
    {
        if ($stock->isLowStock()) {
            $alert = LowStockAlert::query()
                ->where('product_id', $stock->product_id)
                ->where('branch_id', $stock->branch_id)
                ->whereNull('resolved_at')
                ->first();

            if ($alert instanceof LowStockAlert) {
                $alert->forceFill([
                    'quantity_milliunits' => $stock->quantity_milliunits,
                    'minimum_stock_milliunits' => $stock->minimum_stock_milliunits,
                    'last_seen_at' => now(),
                ])->save();

                return;
            }

            LowStockAlert::query()->create([
                'product_id' => $stock->product_id,
                'branch_id' => $stock->branch_id,
                'quantity_milliunits' => $stock->quantity_milliunits,
                'minimum_stock_milliunits' => $stock->minimum_stock_milliunits,
                'opened_at' => now(),
                'last_seen_at' => now(),
            ]);

            return;
        }

        LowStockAlert::query()
            ->where('product_id', $stock->product_id)
            ->where('branch_id', $stock->branch_id)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }
}
