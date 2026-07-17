<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum AdjustmentReason: string
{
    case Recount = 'recount';
    case Breakage = 'breakage';
    case Theft = 'theft';
    case Expiry = 'expiry';
    case Damage = 'damage';
    case Correction = 'correction';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $reason): string => $reason->value,
            self::cases(),
        );
    }
}
