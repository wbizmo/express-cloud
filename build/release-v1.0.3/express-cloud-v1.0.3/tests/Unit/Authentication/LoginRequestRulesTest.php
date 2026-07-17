<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Http\Requests\Auth\LoginRequest;
use PHPUnit\Framework\TestCase;

final class LoginRequestRulesTest extends TestCase
{
    public function test_login_request_uses_only_staff_identity_and_access_key(): void
    {
        $rules = (new LoginRequest)->rules();

        self::assertArrayHasKey('account_public_id', $rules);
        self::assertArrayHasKey('access_key', $rules);
        self::assertArrayNotHasKey('email', $rules);
        self::assertArrayNotHasKey('password', $rules);
    }
}
