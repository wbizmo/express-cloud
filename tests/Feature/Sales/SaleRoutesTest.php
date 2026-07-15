<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class SaleRoutesTest extends TestCase
{
    public function test_sales_routes_require_authentication(): void
    {
        foreach ([
            '/admin/sales',
            '/admin/sales/create',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_sales_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('sales.view', $permissions);
        self::assertContains('sales.create', $permissions);
        self::assertContains('sales.payments', $permissions);
    }
}
