<?php

declare(strict_types=1);

namespace Tests\Unit\Commercial;

use App\Enums\Commercial\DiscountValueType;
use App\Enums\Commercial\VoucherStatus;
use App\Models\DiscountVoucher;
use App\Services\Commercial\VoucherCalculator;
use Tests\TestCase;

final class VoucherCalculatorTest extends TestCase
{
    public function test_percentage_voucher_respects_maximum_cap(): void
    {
        $voucher = new DiscountVoucher([
            'value_type' => DiscountValueType::Percentage,
            'value' => 2000,
            'minimum_sale_kobo' => 0,
            'maximum_discount_kobo' => 150000,
            'status' => VoucherStatus::Active,
            'usage_count' => 0,
        ]);

        self::assertSame(
            150000,
            (new VoucherCalculator)->discountKobo(
                $voucher,
                1000000,
            ),
        );
    }

    public function test_fixed_voucher_never_exceeds_sale(): void
    {
        $voucher = new DiscountVoucher([
            'value_type' => DiscountValueType::Fixed,
            'value' => 500000,
            'minimum_sale_kobo' => 0,
            'status' => VoucherStatus::Active,
            'usage_count' => 0,
        ]);

        self::assertSame(
            100000,
            (new VoucherCalculator)->discountKobo(
                $voucher,
                100000,
            ),
        );
    }
}
