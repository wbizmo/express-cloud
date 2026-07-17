<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Enums\Authentication\AccountStatus;
use PHPUnit\Framework\TestCase;

final class AccountStatusTest extends TestCase
{
    public function test_only_active_accounts_can_authenticate(): void
    {
        self::assertTrue(AccountStatus::Active->canAuthenticate());
        self::assertFalse(AccountStatus::Suspended->canAuthenticate());
        self::assertFalse(AccountStatus::Revoked->canAuthenticate());
    }
}
