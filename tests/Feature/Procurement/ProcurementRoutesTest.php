<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class ProcurementRoutesTest extends TestCase
{
    public function test_procurement_and_low_stock_routes_require_authentication(): void
    {
        foreach ([
            '/admin/procurement/orders',
            '/admin/reports/low-stock',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_procurement_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('procurement.create', $permissions);
        self::assertContains('procurement.receive', $permissions);
        self::assertContains('reports.low-stock', $permissions);
    }
}
