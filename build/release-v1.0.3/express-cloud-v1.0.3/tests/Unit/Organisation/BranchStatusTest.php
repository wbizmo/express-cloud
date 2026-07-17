<?php

declare(strict_types=1);

namespace Tests\Unit\Organisation;

use App\Enums\Organisation\BranchStatus;
use PHPUnit\Framework\TestCase;

final class BranchStatusTest extends TestCase
{
    public function test_only_active_branches_accept_new_operations(): void
    {
        self::assertTrue(
            BranchStatus::Active->acceptsNewOperations(),
        );
        self::assertFalse(
            BranchStatus::Inactive->acceptsNewOperations(),
        );
    }
}
