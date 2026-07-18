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
            '/^[A-HJ-KM-NP-Z]{4}-[A-HJ-KM-NP-Z]{4}$/',
            $key,
        );
    }

    public function test_keys_use_only_approved_letters(): void
    {
        $generator = new LoginKeyGenerator;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $key = $generator->generate();
            $normalized = LoginKeyGenerator::normalize($key);

            self::assertSame(
                LoginKeyGenerator::RAW_LENGTH,
                strlen($normalized),
            );

            self::assertSame(
                LoginKeyGenerator::RAW_LENGTH,
                strspn(
                    $normalized,
                    LoginKeyGenerator::ALPHABET,
                ),
            );

            self::assertStringNotContainsString('I', $key);
            self::assertStringNotContainsString('L', $key);
            self::assertStringNotContainsString('O', $key);
        }
    }

    public function test_normalization_accepts_grouped_keys(): void
    {
        self::assertSame(
            'KMNPQRST',
            LoginKeyGenerator::normalize('kmnp-qrst'),
        );
    }

    public function test_normalization_accepts_spaces_and_lowercase(): void
    {
        self::assertSame(
            'ABCDEFGH',
            LoginKeyGenerator::normalize('abcd efgh'),
        );
    }

    public function test_numbers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginKeyGenerator::normalize('K7M4-P9XR');
    }

    public function test_ambiguous_letters_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginKeyGenerator::normalize('ABCI-EFGL');
    }

    public function test_invalid_length_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginKeyGenerator::normalize('ABCD-EFG');
    }
}
