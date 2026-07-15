<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use App\Models\PurchaseOrderLine;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderLineTest extends TestCase
{
    public function test_remaining_quantity_never_goes_negative(): void
    {
        $line = new PurchaseOrderLine([
            'ordered_quantity_milliunits' => 5000,
            'received_quantity_milliunits' => 7000,
        ]);

        self::assertSame(0, $line->remainingMilliunits());
    }
}
