<?php

declare(strict_types=1);

namespace App\Enums\Sales;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Partial = 'partial';
    case Cancelled = 'cancelled';

    public static function fromPayment(
        int $paidKobo,
        int $totalKobo,
    ): self {
        if ($paidKobo <= 0) {
            return self::Confirmed;
        }

        if ($paidKobo >= $totalKobo) {
            return self::Paid;
        }

        return self::Partial;
    }
}
