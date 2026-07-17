<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Support\Security\LoginKeyGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AlphabetOnlyLoginKeyTest extends TestCase
{
    public function test_generated_keys_are_alphabetic_and_grouped(): void
    {
        $key = (new LoginKeyGenerator)->generate();
        self::assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z]{4}-[A-HJ-KM-NP-Z]{4}$/', $key);
    }

    public function test_normalization_removes_the_hyphen(): void
    {
        self::assertSame('ECBVYKQW', LoginKeyGenerator::normalize('ECBV-YKQW'));
    }

    public function test_numbers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LoginKeyGenerator::normalize('EC19-ABCD');
    }
}
