<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Services\Inventory\Quantity;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function test_quantity_converts_to_milliunits(): void
    {
        $quantity = new Quantity;

        self::assertSame(1250, $quantity->toMilliunits('1.25'));
        self::assertSame(-2500, $quantity->toMilliunits('-2.5'));
    }

    public function test_quantity_formats_without_false_precision(): void
    {
        $quantity = new Quantity;

        self::assertSame('4', $quantity->format(4000));
        self::assertSame('4.125', $quantity->format(4125));
    }
}
