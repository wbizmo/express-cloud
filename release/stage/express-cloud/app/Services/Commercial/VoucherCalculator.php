<?php

declare(strict_types=1);

namespace App\Services\Commercial;

use App\Enums\Commercial\DiscountValueType;
use App\Models\DiscountVoucher;

final class VoucherCalculator
{
    public function discountKobo(
        DiscountVoucher $voucher,
        int $eligibleSubtotalKobo,
    ): int {
        if (! $voucher->available()) {
            throw new \DomainException(
                'This voucher is inactive, expired, or fully redeemed.',
            );
        }

        if ($eligibleSubtotalKobo < $voucher->minimum_sale_kobo) {
            throw new \DomainException(
                'The sale does not meet this voucher minimum.',
            );
        }

        $discount = $voucher->value_type === DiscountValueType::Fixed
            ? $voucher->value
            : (int) round(
                $eligibleSubtotalKobo * ($voucher->value / 10000),
            );

        if ($voucher->maximum_discount_kobo !== null) {
            $discount = min(
                $discount,
                $voucher->maximum_discount_kobo,
            );
        }

        return max(0, min($eligibleSubtotalKobo, $discount));
    }
}
