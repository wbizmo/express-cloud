<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Services\Catalog\MoneyInput;
use PHPUnit\Framework\TestCase;

final class MoneyInputTest extends TestCase
{
    public function test_naira_input_is_converted_to_integer_kobo(): void
    {
        self::assertSame(
            125_050,
            (new MoneyInput)->toKobo('1250.50'),
        );
    }

    public function test_empty_optional_money_returns_null(): void
    {
        self::assertNull((new MoneyInput)->toKobo(null));
        self::assertNull((new MoneyInput)->toKobo(''));
    }
}
