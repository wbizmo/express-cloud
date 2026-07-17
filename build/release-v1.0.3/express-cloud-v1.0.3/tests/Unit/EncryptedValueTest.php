<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\EncryptedValue;
use PHPUnit\Framework\TestCase;

final class EncryptedValueTest extends TestCase
{
    public function test_sensitive_value_round_trips_without_plaintext_storage(): void
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $service = new EncryptedValue($key, 3);

        $payload = $service->encrypt('EMP-LOGIN-KEY-001');

        self::assertStringNotContainsString('EMP-LOGIN-KEY-001', $payload);
        self::assertSame('EMP-LOGIN-KEY-001', $service->decrypt($payload));
        self::assertStringContainsString('"v":3', $payload);
    }
}
