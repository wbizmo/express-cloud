<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money\Naira;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NairaTest extends TestCase
{
    public function test_values_are_stored_as_integer_kobo(): void
    {
        self::assertSame(1_250_075, Naira::fromNaira('12500.75')->kobo);
    }

    public function test_values_format_without_floating_point_arithmetic(): void
    {
        self::assertSame('₦12,500.75', Naira::fromKobo(1_250_075)->format());
        self::assertSame('₦12,500', Naira::fromKobo(1_250_000)->format());
    }

    public function test_arithmetic_is_immutable(): void
    {
        $base = Naira::fromNaira('1000');
        $total = $base->multiply(3)->add(Naira::fromNaira('250'));

        self::assertSame(100_000, $base->kobo);
        self::assertSame(325_000, $total->kobo);
    }

    public function test_invalid_precision_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Naira::fromNaira('10.999');
    }
}
