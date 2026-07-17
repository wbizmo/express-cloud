<?php

declare(strict_types=1);

namespace App\Enums\AccountingOperations;

enum FixedAssetStatus: string
{
    case Active = 'active';
    case Disposed = 'disposed';
    case WrittenOff = 'written_off';
}
