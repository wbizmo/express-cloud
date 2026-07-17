<?php

declare(strict_types=1);

namespace App\Enums\Authentication;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
