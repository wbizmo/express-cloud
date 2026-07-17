<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';
}
