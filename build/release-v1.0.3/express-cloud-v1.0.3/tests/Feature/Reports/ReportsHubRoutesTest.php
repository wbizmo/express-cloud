<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use Tests\TestCase;

final class ReportsHubRoutesTest extends TestCase
{
    public function test_reports_and_exports_require_authentication(): void
    {
        foreach ([
            '/admin/reports',
            '/admin/reports/exports/sales',
            '/admin/reports/exports/staff',
            '/admin/reports/exports/low-stock',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }
}
