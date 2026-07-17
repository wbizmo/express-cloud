<?php

declare(strict_types=1);

namespace App\Enums\Organisation;

enum BranchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function acceptsNewOperations(): bool
    {
        return $this === self::Active;
    }
}
