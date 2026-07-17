<?php

declare(strict_types=1);

namespace Tests\Feature\Commercial;

use PHPUnit\Framework\TestCase;

final class EnterpriseControlsTest extends TestCase
{
    public function test_global_assets_include_sortable_and_toggle_controls(): void
    {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root.'/resources/css/app.css');
        $js = file_get_contents($root.'/resources/js/app.js');

        self::assertIsString($css);
        self::assertIsString($js);
        self::assertStringContainsString('.ec-toggle-track', $css);
        self::assertStringContainsString('sortSelectOptions', $js);
        self::assertStringContainsString('select:not([data-no-sort])', $js);
    }
}
