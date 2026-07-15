<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class CatalogRoutesTest extends TestCase
{
    public function test_catalog_routes_require_authentication(): void
    {
        foreach ([
            '/admin/catalog/products',
            '/admin/catalog/products/create',
            '/admin/catalog/categories',
            '/admin/catalog/brands',
            '/admin/catalog/tax-rates',
            '/admin/catalog/suppliers',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_permission_catalog_includes_catalog_controls(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('products.create', $permissions);
        self::assertContains('categories.manage', $permissions);
        self::assertContains('brands.manage', $permissions);
        self::assertContains('suppliers.create', $permissions);
    }
}
