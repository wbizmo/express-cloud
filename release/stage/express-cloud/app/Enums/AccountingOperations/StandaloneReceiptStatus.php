<?php

declare(strict_types=1);

namespace App\Enums\AccountingOperations;

enum StandaloneReceiptStatus: string
{
    case Received = 'received';
    case Voided = 'voided';
}
