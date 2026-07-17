<?php

declare(strict_types=1);

namespace Tests\Feature\Commercial;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class CommercialRoutesTest extends TestCase
{
    public function test_commercial_admin_routes_require_authentication(): void
    {
        foreach ([
            '/admin/commercial/vouchers',
            '/admin/commercial/receivables',
            '/admin/commercial/purchases',
            '/admin/commercial/purchases/create',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_commercial_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        foreach ([
            'sales.view.own',
            'sales.view.all',
            'sales.payments.record',
            'sales.returns.create',
            'vouchers.manage',
            'vouchers.apply',
            'customers.receivables.view',
            'purchases.record',
        ] as $permission) {
            self::assertContains($permission, $permissions);
        }
    }
}
