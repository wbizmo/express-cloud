<?php

declare(strict_types=1);

namespace Tests\Unit\Imports;

use App\Enums\Imports\ImportStatus;
use PHPUnit\Framework\TestCase;

final class ImportStatusTest extends TestCase
{
    public function test_only_completed_and_failed_states_are_terminal(): void
    {
        self::assertTrue(ImportStatus::Completed->terminal());
        self::assertTrue(ImportStatus::Failed->terminal());
        self::assertTrue(ImportStatus::FailedValidation->terminal());
        self::assertFalse(ImportStatus::Validated->terminal());
        self::assertFalse(ImportStatus::Processing->terminal());
    }
}
