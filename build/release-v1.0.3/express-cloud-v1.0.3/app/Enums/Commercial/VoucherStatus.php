<?php

declare(strict_types=1);

namespace App\Enums\Commercial;

enum VoucherStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
