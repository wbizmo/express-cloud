<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case PurchaseReturn = 'purchase_return';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';
    case Reservation = 'reservation';
    case Release = 'release';
    case StockCount = 'stock_count';
    case Quarantine = 'quarantine';
    case Damage = 'damage';
    case LandedCost = 'landed_cost';
    case CostReversal = 'cost_reversal';
}
