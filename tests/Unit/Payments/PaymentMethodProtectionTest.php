<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Models\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodProtectionTest extends TestCase
{
    public function test_system_defaults_cannot_be_deleted(): void
    {
        $method = new PaymentMethod([
            'is_system_default' => true,
        ]);

        self::assertFalse($method->mayBeDeleted());
    }
}
