<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class OperationsRoutesTest extends TestCase
{
    public function test_admin_operations_routes_require_authentication(): void
    {
        foreach ([
            '/admin/dashboard',
            '/admin/operations/alert-recipients',
            '/admin/operations/settings',
            '/admin/reports/staff-performance',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_operations_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('dashboard.view', $permissions);
        self::assertContains(
            'alerts.manage-recipients',
            $permissions,
        );
        self::assertContains(
            'settings.business.manage',
            $permissions,
        );
        self::assertContains(
            'reports.staff-performance',
            $permissions,
        );
    }

    public function test_invalid_cron_secret_is_hidden_as_not_found(): void
    {
        config()->set('operations.cron_secret', 'correct-secret');

        $this->get(
            '/cron/wrong-secret/end-of-day-digest',
        )->assertNotFound();
    }
}
