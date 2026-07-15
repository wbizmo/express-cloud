<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\LoginKeyGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LoginKeyGeneratorTest extends TestCase
{
    public function test_generated_keys_are_grouped_for_readability(): void
    {
        $key = (new LoginKeyGenerator)->generate();

        self::assertMatchesRegularExpression(
            '/^[A-HJ-KM-NP-Z2-9]{4}-[A-HJ-KM-NP-Z2-9]{4}$/',
            $key,
        );
    }

    public function test_keys_use_no_ambiguous_characters(): void
    {
        $generator = new LoginKeyGenerator;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $key = $generator->generate();

            self::assertStringNotContainsString('0', $key);
            self::assertStringNotContainsString('O', $key);
            self::assertStringNotContainsString('1', $key);
            self::assertStringNotContainsString('I', $key);
            self::assertStringNotContainsString('L', $key);
        }
    }

    public function test_normalization_accepts_grouped_keys(): void
    {
        self::assertSame(
            'K7M4P9XR',
            LoginKeyGenerator::normalize('k7m4-p9xr'),
        );
    }

    public function test_invalid_keys_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginKeyGenerator::normalize('12345678');
    }
}
