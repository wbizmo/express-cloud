<?php

declare(strict_types=1);

namespace App\Enums\Catalog;

enum RecordStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function selectable(): bool
    {
        return $this === self::Active;
    }
}
