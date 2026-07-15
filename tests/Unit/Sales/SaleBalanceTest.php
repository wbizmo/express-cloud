<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Models\Sale;
use PHPUnit\Framework\TestCase;

final class SaleBalanceTest extends TestCase
{
    public function test_balance_due_never_goes_negative(): void
    {
        $sale = new Sale([
            'grand_total_kobo' => 1000,
            'paid_amount_kobo' => 1200,
        ]);

        self::assertSame(0, $sale->balanceDueKobo());
    }
}
