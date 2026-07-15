<?php

declare(strict_types=1);

namespace App\Enums\SupplierFinance;

enum SupplierBillStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public static function fromPayment(
        int $paidKobo,
        int $totalKobo,
    ): self {
        if ($paidKobo <= 0) {
            return self::Open;
        }

        if ($paidKobo >= $totalKobo) {
            return self::Paid;
        }

        return self::Partial;
    }

    public function payable(): bool
    {
        return in_array(
            $this,
            [self::Open, self::Partial],
            true,
        );
    }
}
