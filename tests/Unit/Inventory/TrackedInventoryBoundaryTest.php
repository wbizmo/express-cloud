<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Models\Product;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Procurement\LowStockAlertService;
use PHPUnit\Framework\TestCase;

final class TrackedInventoryBoundaryTest extends TestCase
{
    public function test_untracked_product_is_rejected_before_database_work(): void
    {
        $ledger = new StockLedger(
            new Quantity,
            new LowStockAlertService,
        );

        $product = new Product([
            'name' => 'Delivery fee',
            'track_inventory' => false,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(
            'Untracked products cannot have stock movements.',
        );

        $reflection = new \ReflectionMethod(
            $ledger,
            'assertTracked',
        );
        $reflection->invoke($ledger, $product);
    }
}
