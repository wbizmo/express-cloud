<?php

declare(strict_types=1);

namespace Tests\Unit\SupplierFinance;

use App\Models\SupplierBill;
use PHPUnit\Framework\TestCase;

final class SupplierBillBalanceTest extends TestCase
{
    public function test_balance_due_never_goes_negative(): void
    {
        $bill = new SupplierBill([
            'total_kobo' => 1000,
            'paid_kobo' => 1250,
        ]);

        self::assertSame(0, $bill->balanceDueKobo());
    }
}
