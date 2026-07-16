<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Services\Api\ApiTokenGenerator;
use PHPUnit\Framework\TestCase;

final class ApiTokenGeneratorTest extends TestCase
{
    public function test_plaintext_is_not_equal_to_stored_hash(): void
    {
        $generated = (new ApiTokenGenerator)->generate();

        self::assertStringStartsWith(
            'ec_live_',
            $generated['plaintext'],
        );
        self::assertSame(
            hash('sha256', $generated['plaintext']),
            $generated['hash'],
        );
        self::assertNotSame(
            $generated['plaintext'],
            $generated['hash'],
        );
    }
}
