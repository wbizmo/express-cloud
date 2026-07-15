<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Enums\Operations\AdminNotificationType;
use PHPUnit\Framework\TestCase;

final class AdminNotificationTypeTest extends TestCase
{
    public function test_low_stock_notification_type_is_explicit(): void
    {
        self::assertSame(
            'low_stock',
            AdminNotificationType::LowStock->value,
        );
    }
}
