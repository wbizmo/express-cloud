<?php

declare(strict_types=1);

namespace App\Enums\Operations;

enum AdminNotificationType: string
{
    case LowStock = 'low_stock';
    case Digest = 'digest';
    case Security = 'security';
    case Operational = 'operational';
}
