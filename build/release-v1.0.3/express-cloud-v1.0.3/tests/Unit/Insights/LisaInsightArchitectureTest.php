<?php

declare(strict_types=1);

namespace Tests\Unit\Insights;

use PHPUnit\Framework\TestCase;

final class LisaInsightArchitectureTest extends TestCase
{
    public function test_lisa_does_not_contain_arbitrary_sql_or_credentials(): void
    {
        $source = file_get_contents(app_path('Services/Insights/LisaInsightEngine.php'));
        self::assertIsString($source);
        self::assertStringNotContainsString('DB::unprepared', $source);
        self::assertStringNotContainsString('login_key', $source);
        self::assertStringNotContainsString('APP_KEY', $source);
    }
}
