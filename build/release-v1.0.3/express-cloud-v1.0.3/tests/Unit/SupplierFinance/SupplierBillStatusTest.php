<?php

declare(strict_types=1);

namespace Tests\Unit\SupplierFinance;

use App\Enums\SupplierFinance\SupplierBillStatus;
use PHPUnit\Framework\TestCase;

final class SupplierBillStatusTest extends TestCase
{
    public function test_payment_totals_map_to_bill_status(): void
    {
        self::assertSame(
            SupplierBillStatus::Open,
            SupplierBillStatus::fromPayment(0, 1000),
        );
        self::assertSame(
            SupplierBillStatus::Partial,
            SupplierBillStatus::fromPayment(500, 1000),
        );
        self::assertSame(
            SupplierBillStatus::Paid,
            SupplierBillStatus::fromPayment(1000, 1000),
        );
    }
}
