<?php

declare(strict_types=1);

namespace App\Enums\AccountingOperations;

enum PurchaseReturnStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
