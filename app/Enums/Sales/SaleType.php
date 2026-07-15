<?php

declare(strict_types=1);

namespace App\Enums\Sales;

enum SaleType: string
{
    case Invoice = 'invoice';
    case Quote = 'quote';
    case Pos = 'pos';

    public function movesStock(): bool
    {
        return $this !== self::Quote;
    }

    public function codePrefix(): string
    {
        return match ($this) {
            self::Invoice => 'INV',
            self::Quote => 'QUO',
            self::Pos => 'POS',
        };
    }
}
