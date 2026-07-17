<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;

final class PriceResolver
{
    public function forBranch(Product $product, string $branchId): int
    {
        $override = $product->branchPrices()
            ->where('branch_id', $branchId)
            ->value('price_kobo');

        return is_numeric($override)
            ? (int) $override
            : $product->default_price_kobo;
    }
}
