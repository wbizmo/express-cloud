<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Enums\Catalog\RecordStatus;
use PHPUnit\Framework\TestCase;

final class RecordStatusTest extends TestCase
{
    public function test_only_active_records_are_selectable(): void
    {
        self::assertTrue(RecordStatus::Active->selectable());
        self::assertFalse(RecordStatus::Inactive->selectable());
    }
}
