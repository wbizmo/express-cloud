<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\SensitiveData;
use PHPUnit\Framework\TestCase;

final class SensitiveDataTest extends TestCase
{
    public function test_sensitive_fields_are_registered_for_redaction(): void
    {
        self::assertContains('password', SensitiveData::forbiddenLogFields());
        self::assertContains('login_key', SensitiveData::forbiddenLogFields());
        self::assertContains(
            'data_encryption_key',
            SensitiveData::forbiddenLogFields(),
        );
    }
}
