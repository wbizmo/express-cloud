<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Enums\Sales\SaleType;
use PHPUnit\Framework\TestCase;

final class SaleTypeTest extends TestCase
{
    public function test_quotes_do_not_move_stock(): void
    {
        self::assertFalse(SaleType::Quote->movesStock());
        self::assertTrue(SaleType::Invoice->movesStock());
        self::assertTrue(SaleType::Pos->movesStock());
    }
}
