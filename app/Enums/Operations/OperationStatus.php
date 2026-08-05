<?php

declare(strict_types=1);

namespace App\Enums\Operations;

enum OperationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
