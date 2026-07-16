<?php

declare(strict_types=1);

namespace App\Enums\Accounting;

enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
