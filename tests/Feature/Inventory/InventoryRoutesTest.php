<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class InventoryRoutesTest extends TestCase
{
    public function test_inventory_routes_require_authentication(): void
    {
        foreach ([
            '/admin/inventory',
            '/admin/inventory/movements',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_inventory_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('inventory.intake', $permissions);
        self::assertContains('inventory.transfer', $permissions);
        self::assertContains('inventory.adjust', $permissions);
    }
}
