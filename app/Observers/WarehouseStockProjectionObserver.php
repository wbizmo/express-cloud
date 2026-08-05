<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\StockMovement;
use App\Services\Inventory\WarehouseStockLedger;

final readonly class WarehouseStockProjectionObserver
{
    public function __construct(private WarehouseStockLedger $ledger) {}

    public function created(StockMovement $movement): void
    {
        $this->ledger->projectLegacyMovement($movement);
    }
}
