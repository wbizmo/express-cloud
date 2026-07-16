<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Enums\Accounting\AccountType;
use PHPUnit\Framework\TestCase;

final class AccountTypeTest extends TestCase
{
    public function test_normal_balances_are_explicit(): void
    {
        self::assertSame('debit', AccountType::Asset->normalSide());
        self::assertSame('debit', AccountType::Expense->normalSide());
        self::assertSame('credit', AccountType::Liability->normalSide());
        self::assertSame('credit', AccountType::Equity->normalSide());
        self::assertSame('credit', AccountType::Revenue->normalSide());
    }
}
