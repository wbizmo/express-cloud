<?php

declare(strict_types=1);

namespace App\Enums\Commercial;

enum PurchaseReceiptStatus: string
{
    case Recorded = 'recorded';
    case Cancelled = 'cancelled';
    case Voided = 'voided';
}
