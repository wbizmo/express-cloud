<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class AccountingRoutesTest extends TestCase
{
    public function test_accounting_reports_require_authentication(): void
    {
        $this->get('/admin/accounting/reports')->assertRedirect('/');
    }

    public function test_accounting_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        foreach ([
            'accounting.accounts.view',
            'accounting.accounts.manage',
            'accounting.journals.view',
            'accounting.journals.create',
            'accounting.journals.reverse',
            'accounting.periods.manage',
            'accounting.reports.view',
            'accounting.sync',
            'accounting.depreciation.post',
        ] as $permission) {
            self::assertContains($permission, $permissions);
        }
    }
}
