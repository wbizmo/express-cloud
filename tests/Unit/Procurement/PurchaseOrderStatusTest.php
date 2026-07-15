<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use App\Enums\Procurement\PurchaseOrderStatus;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderStatusTest extends TestCase
{
    public function test_only_approved_and_partial_orders_are_receivable(): void
    {
        self::assertTrue(PurchaseOrderStatus::Approved->receivable());
        self::assertTrue(PurchaseOrderStatus::PartiallyReceived->receivable());
        self::assertFalse(PurchaseOrderStatus::Draft->receivable());
        self::assertFalse(PurchaseOrderStatus::Received->receivable());
    }
}
