<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class DocumentRoutesTest extends TestCase
{
    public function test_document_routes_require_authentication(): void
    {
        foreach ([
            '/admin/documents/sales/01TEST/thermal',
            '/admin/documents/sales/01TEST/a4',
            '/admin/documents/sales/01TEST/pdf',
            '/admin/documents/products/01TEST/label',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_document_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains(
            'documents.sales.print',
            $permissions,
        );
        self::assertContains(
            'documents.products.labels',
            $permissions,
        );
    }
}
