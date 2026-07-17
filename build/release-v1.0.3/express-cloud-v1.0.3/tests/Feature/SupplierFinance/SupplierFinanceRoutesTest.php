<?php

declare(strict_types=1);

namespace Tests\Feature\SupplierFinance;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class SupplierFinanceRoutesTest extends TestCase
{
    public function test_supplier_finance_routes_require_authentication(): void
    {
        foreach ([
            '/admin/supplier-finance/bills',
            '/admin/supplier-finance/returns',
            '/admin/reports/supplier-balances',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_supplier_finance_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('supplier-bills.create', $permissions);
        self::assertContains('supplier-bills.pay', $permissions);
        self::assertContains('supplier-returns.create', $permissions);
        self::assertContains(
            'reports.supplier-balances',
            $permissions,
        );
    }
}
