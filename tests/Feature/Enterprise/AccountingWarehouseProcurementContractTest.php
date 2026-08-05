<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Support\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AccountingWarehouseProcurementContractTest extends TestCase
{
    public function test_enterprise_permissions_are_catalogued(): void
    {
        $permissions = PermissionCatalog::all();

        foreach ([
            'accounting.enterprise.view',
            'accounting.close',
            'accounting.bank-reconcile',
            'accounting.treasury.manage',
            'warehouses.view',
            'warehouses.manage',
            'warehouses.operate',
            'inventory.reservations.manage',
            'inventory.counts.manage',
            'procurement.requisitions.view',
            'procurement.requisitions.create',
            'procurement.requisitions.approve',
            'procurement.receipts.create',
            'procurement.landed-cost.manage',
            'supplier-credits.manage',
        ] as $permission) {
            self::assertContains($permission, $permissions);
        }
    }

    #[DataProvider('sourceContracts')]
    public function test_accounting_inventory_and_procurement_source_contracts(
        string $path,
        string $needle,
    ): void {
        $source = file_get_contents(base_path($path));
        self::assertIsString($source);
        self::assertStringContainsString($needle, $source);
    }

    /** @return array<string, array{string, string}> */
    public static function sourceContracts(): array
    {
        return [
            'warehouse stable lock order' => [
                'app/Services/Inventory/WarehouseStockLedger.php',
                'usort($pairs',
            ],
            'warehouse append-only movement' => [
                'app/Services/Inventory/WarehouseStockLedger.php',
                'StockMovement::query()->create',
            ],
            'warehouse projection observer' => [
                'app/Providers/AppServiceProvider.php',
                'WarehouseStockProjectionObserver::class',
            ],
            'financial controls' => [
                'app/Services/Accounting/EnterpriseFinancialStatements.php',
                'controlReconciliation',
            ],
            'period close zero-difference gate' => [
                'app/Services/Accounting/PeriodCloseService.php',
                'The period cannot close until',
            ],
            'bank unmatched-balance protection' => [
                'app/Services/Accounting/BankReconciliationService.php',
                'The reconciliation amount exceeds an unmatched balance.',
            ],
            'bank finalization requires resolved lines' => [
                'app/Services/Accounting/BankReconciliationService.php',
                'Every bank statement line must be matched or explicitly ignored before finalization.',
            ],
            'procurement backorder calculation' => [
                'app/Services/Procurement/EnterpriseProcurementWorkflow.php',
                'backordered_quantity_milliunits',
            ],
            'landed cost capitalization' => [
                'app/Services/Procurement/EnterpriseProcurementWorkflow.php',
                'capitalizeCost',
            ],
            'accrual idempotency' => [
                'app/Services/Accounting/AccrualService.php',
                "'accounting.accrual.create'",
            ],
            'asset disposal journal' => [
                'app/Services/Accounting/AssetDisposalService.php',
                "'asset-disposal'",
            ],
            'combined migration' => [
                'database/migrations/2026_08_05_130000_create_enterprise_accounting_inventory_procurement.php',
                "Schema::create('warehouse_stock_balances'",
            ],
        ];
    }
}
