<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Enums\Sales\SaleStatus;
use PHPUnit\Framework\TestCase;

final class SaleStatusTest extends TestCase
{
    public function test_payment_amount_maps_to_sale_status(): void
    {
        self::assertSame(
            SaleStatus::Confirmed,
            SaleStatus::fromPayment(0, 1000),
        );
        self::assertSame(
            SaleStatus::Partial,
            SaleStatus::fromPayment(500, 1000),
        );
        self::assertSame(
            SaleStatus::Paid,
            SaleStatus::fromPayment(1000, 1000),
        );
    }
}
