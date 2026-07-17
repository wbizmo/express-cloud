<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class CustomerRoutesTest extends TestCase
{
    public function test_customer_routes_require_authentication(): void
    {
        foreach ([
            '/admin/customers',
            '/admin/customers/search?search=ab',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_customer_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('customers.view', $permissions);
        self::assertContains('customers.create', $permissions);
    }
}
