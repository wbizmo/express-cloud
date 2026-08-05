<?php

declare(strict_types=1);

namespace App\Enums\Sales;

enum SaleType: string
{
    case Invoice = 'invoice';
    case Order = 'order';
    case Quote = 'quote';
    case Pos = 'pos';

    public function movesStock(): bool
    {
        return in_array($this, [self::Invoice, self::Pos], true);
    }

    public function isPreFinancial(): bool
    {
        return in_array($this, [self::Quote, self::Order], true);
    }

    public function codePrefix(): string
    {
        return match ($this) {
            self::Invoice => 'INV',
            self::Order => 'ORD',
            self::Quote => 'QUO',
            self::Pos => 'POS',
        };
    }
}
