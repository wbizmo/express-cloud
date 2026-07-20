<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;

final class StockService
{
    /**
     * Increment stock for a product in a specific branch.
     *
     * @param string $productId
     * @param string $branchId
     * @param int $quantityMilliunits
     * @return void
     */
    public function incrementStock(string $productId, string $branchId, int $quantityMilliunits): void
    {
        if ($quantityMilliunits <= 0) {
            return;
        }

        ProductBranchStock::query()->updateOrCreate(
            ['product_id' => $productId, 'branch_id' => $branchId],
            ['quantity_milliunits' => DB::raw("quantity_milliunits + $quantityMilliunits")]
        );
    }

    /**
     * Decrement stock for a product in a specific branch.
     *
     * @param string $productId
     * @param string $branchId
     * @param int $quantityMilliunits
     * @return void
     * @throws \RuntimeException if stock becomes negative
     */
    public function decrementStock(string $productId, string $branchId, int $quantityMilliunits): void
    {
        if ($quantityMilliunits <= 0) {
            return;
        }

        $stock = ProductBranchStock::query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$stock) {
            throw new \RuntimeException("Stock record not found for product {$productId} in branch {$branchId}");
        }

        if ($stock->quantity_milliunits < $quantityMilliunits) {
            throw new \RuntimeException("Insufficient stock: requested {$quantityMilliunits}, available {$stock->quantity_milliunits}");
        }

        $stock->decrement('quantity_milliunits', $quantityMilliunits);
    }
}
