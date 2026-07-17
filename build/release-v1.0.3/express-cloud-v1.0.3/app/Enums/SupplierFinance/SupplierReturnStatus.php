<?php

declare(strict_types=1);

namespace App\Enums\SupplierFinance;

enum SupplierReturnStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
