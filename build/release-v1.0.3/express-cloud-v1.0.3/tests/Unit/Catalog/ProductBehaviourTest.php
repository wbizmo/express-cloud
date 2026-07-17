<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

final class ProductBehaviourTest extends TestCase
{
    public function test_untracked_items_have_an_explicit_label(): void
    {
        $product = new Product(['track_inventory' => false]);

        self::assertSame('Untracked item', $product->inventoryLabel());
    }

    public function test_tracked_items_have_an_explicit_label(): void
    {
        $product = new Product(['track_inventory' => true]);

        self::assertSame(
            'Tracked inventory',
            $product->inventoryLabel(),
        );
    }
}
