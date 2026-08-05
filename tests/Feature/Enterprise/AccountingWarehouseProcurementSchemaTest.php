<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\Branch;
use App\Models\LedgerAccount;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Services\Accounting\EnterpriseFinancialStatements;
use App\Services\Inventory\InventoryValuationService;
use Carbon\CarbonImmutable;
use Database\Seeders\EnterpriseAccountingInventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AccountingWarehouseProcurementSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_accounting_and_warehouse_schema_is_complete(): void
    {
        foreach ([
            'warehouses',
            'units_of_measure',
            'product_variants',
            'inventory_batches',
            'inventory_serials',
            'warehouse_stock_balances',
            'stock_reservations',
            'stock_counts',
            'stock_count_lines',
            'reorder_rules',
            'purchase_requisitions',
            'purchase_requisition_lines',
            'goods_receipts',
            'goods_receipt_lines',
            'landed_cost_allocations',
            'supplier_credit_notes',
            'accounting_close_batches',
            'bank_accounts',
            'bank_statement_imports',
            'bank_statement_lines',
            'bank_reconciliation_matches',
            'treasury_accounts',
            'treasury_movements',
            'accrual_schedules',
            'accrual_postings',
            'cash_counters',
            'cash_counter_movements',
            'supplier_credit_applications',
            'asset_disposals',
            'inventory_valuation_snapshots',
        ] as $table) {
            self::assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        self::assertTrue(Schema::hasColumns('stock_movements', [
            'warehouse_id',
            'product_variant_id',
            'inventory_batch_id',
            'inventory_serial_id',
            'stock_condition',
            'inventory_value_after_kobo',
        ]));
        self::assertTrue(Schema::hasColumns('journal_lines', [
            'warehouse_id',
            'tax_rate_id',
            'due_on',
            'subledger_reference',
        ]));
        self::assertTrue(Schema::hasColumns('journal_entries', [
            'book_type',
            'accounting_close_batch_id',
            'locked_at',
        ]));
    }

    public function test_enterprise_seed_and_empty_financial_controls_are_deterministic(): void
    {
        Branch::query()->create([
            'name' => 'Enterprise Branch',
            'code' => 'ENT',
            'address' => 'Test',
            'status' => 'active',
            'is_head_office' => true,
        ]);
        foreach ([
            ['1000', 'Cash', 'asset'],
            ['1010', 'Bank', 'asset'],
            ['1020', 'POS Clearing', 'asset'],
            ['1100', 'Receivables', 'asset'],
            ['1200', 'Inventory', 'asset'],
            ['2000', 'Payables', 'liability'],
        ] as [$code, $name, $type]) {
            LedgerAccount::query()->create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'is_control_account' => true,
                'is_system' => true,
                'is_active' => true,
                'allow_manual_posting' => false,
            ]);
        }

        $this->seed(EnterpriseAccountingInventorySeeder::class);

        self::assertSame(1, Warehouse::query()->where('is_default', true)->count());
        self::assertTrue(UnitOfMeasure::query()->where('code', 'EA')->exists());

        $asOf = CarbonImmutable::parse('2026-08-05');
        $statements = app(EnterpriseFinancialStatements::class);
        self::assertSame(0, $statements->balanceSheet($asOf)['difference_kobo']);
        self::assertSame(0, $statements->controlReconciliation($asOf)['total_difference_kobo']);

        $audit = app(InventoryValuationService::class)->audit();
        self::assertSame(0, $audit['negative_rows']);
        self::assertSame(0, $audit['reserved_overruns']);
    }

    public function test_enterprise_routes_are_registered(): void
    {
        self::assertStringContainsString(
            '/admin/accounting/enterprise',
            route('admin.accounting.enterprise.index'),
        );
        self::assertStringContainsString(
            '/admin/warehouses',
            route('admin.warehouses.index'),
        );
        self::assertStringContainsString(
            '/admin/procurement/enterprise',
            route('admin.procurement.enterprise.index'),
        );
    }
}
