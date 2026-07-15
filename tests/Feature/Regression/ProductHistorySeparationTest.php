<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use PHPUnit\Framework\TestCase;

final class ProductHistorySeparationTest extends TestCase
{
    public function test_product_controller_does_not_delete_history(): void
    {
        $paths = glob(
            app_path('Http/Controllers/Admin/Catalog/*Product*Controller.php'),
        );

        self::assertNotFalse($paths);

        foreach ($paths as $path) {
            $content = file_get_contents($path);
            self::assertIsString($content);
            self::assertStringNotContainsString(
                'stockMovements()->delete',
                $content,
            );
            self::assertStringNotContainsString(
                'activityLogs()->delete',
                $content,
            );
        }
    }
}
