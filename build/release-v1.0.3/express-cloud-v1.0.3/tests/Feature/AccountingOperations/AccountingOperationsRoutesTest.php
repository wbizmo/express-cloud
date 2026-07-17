<?php

declare(strict_types=1);

namespace Tests\Feature\AccountingOperations;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class AccountingOperationsRoutesTest extends TestCase
{
    public function test_routes_require_authentication(): void
    {
        foreach ([
            '/admin/accounting-operations/branding',
            '/admin/accounting-operations/receipts',
            '/admin/accounting-operations/receipts/create',
            '/admin/accounting-operations/purchase-returns',
            '/admin/accounting-operations/purchase-returns/create',
            '/admin/accounting-operations/assets',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        foreach ([
            'documents.branding.manage',
            'receipts.view',
            'receipts.create',
            'purchase_returns.view',
            'purchase_returns.create',
            'assets.view',
            'assets.manage',
            'operation_documents.download',
        ] as $permission) {
            self::assertContains($permission, $permissions);
        }
    }
}
