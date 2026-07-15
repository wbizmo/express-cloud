<?php

declare(strict_types=1);

namespace Tests\Feature\Imports;

use App\Exports\ProductImportTemplateExport;
use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class ProductImportRoutesTest extends TestCase
{
    public function test_import_routes_require_authentication(): void
    {
        foreach ([
            '/admin/imports/products',
            '/admin/imports/products/template',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_template_headers_contain_enterprise_product_fields(): void
    {
        self::assertContains(
            'supplier_code',
            ProductImportTemplateExport::HEADERS,
        );
        self::assertContains(
            'track_inventory',
            ProductImportTemplateExport::HEADERS,
        );
        self::assertContains(
            'default_cost_price',
            ProductImportTemplateExport::HEADERS,
        );
    }

    public function test_import_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('products.import', $permissions);
        self::assertContains('products.import-history', $permissions);
    }
}
