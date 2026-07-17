<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use PHPUnit\Framework\TestCase;

final class OperationDocumentTemplateTest extends TestCase
{
    public function test_report_template_has_optional_logo_guard(): void
    {
        $path = dirname(__DIR__, 3)
            .'/resources/views/documents/operations/report.blade.php';
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString(
            "\$branding['logo_data_uri']",
            $contents,
        );
        self::assertStringNotContainsString(
            'http://',
            $contents,
        );
        self::assertStringNotContainsString(
            'https://',
            $contents,
        );
    }
}
