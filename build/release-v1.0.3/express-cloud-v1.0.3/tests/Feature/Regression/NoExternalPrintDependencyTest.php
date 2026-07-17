<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NoExternalPrintDependencyTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function templates(): array
    {
        $resources = dirname(__DIR__, 3).'/resources/views/documents';

        return [
            'thermal' => [
                $resources.'/sale-thermal.blade.php',
            ],
            'a4' => [
                $resources.'/sale-a4.blade.php',
            ],
        ];
    }

    #[DataProvider('templates')]
    public function test_print_templates_have_no_external_urls(
        string $path,
    ): void {
        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);
        self::assertStringNotContainsString(
            'http://',
            $content,
        );
        self::assertStringNotContainsString(
            'https://',
            $content,
        );
        self::assertStringContainsString(
            'branch?->address',
            $content,
        );
    }
}
