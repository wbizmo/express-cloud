<?php

declare(strict_types=1);

namespace App\Enums\Commercial;

enum SaleReturnStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
