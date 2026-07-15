<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Models\BusinessSetting;
use PHPUnit\Framework\TestCase;

final class BusinessSettingTest extends TestCase
{
    public function test_session_inactivity_is_an_integer_setting(): void
    {
        $setting = new BusinessSetting([
            'session_inactivity_minutes' => '20',
        ]);

        self::assertSame(
            20,
            $setting->session_inactivity_minutes,
        );
    }
}
