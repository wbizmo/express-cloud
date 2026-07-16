<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use PHPUnit\Framework\TestCase;

final class QuoteConversionGuardTest extends TestCase
{
    public function test_existing_quote_conversion_action_is_preserved(): void
    {
        $path = dirname(__DIR__, 3).'/app/Actions/Sales/ConvertQuote.php';
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);
        self::assertStringContainsString('final readonly class ConvertQuote', $content);
    }
}
