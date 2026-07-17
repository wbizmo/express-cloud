<?php

declare(strict_types=1);

namespace Tests\Unit\Customers;

use App\Models\Customer;
use PHPUnit\Framework\TestCase;

final class CustomerCreditTest extends TestCase
{
    public function test_available_credit_never_goes_negative(): void
    {
        $customer = new Customer([
            'credit_limit_kobo' => 100_000,
            'balance_kobo' => 125_000,
        ]);

        self::assertSame(0, $customer->availableCreditKobo());
    }
}
