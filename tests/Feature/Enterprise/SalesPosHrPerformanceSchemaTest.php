<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SalesPosHrPerformanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_six_to_nine_schema_is_complete(): void
    {
        foreach ([
            'customer_groups',
            'commercial_approval_requests',
            'sales_document_events',
            'sales_deliveries',
            'sales_delivery_lines',
            'pos_terminals',
            'pos_shifts',
            'pos_shift_tenders',
            'pos_cash_movements',
            'pos_held_sales',
            'pos_receipt_prints',
            'departments',
            'job_roles',
            'employees',
            'employee_assignments',
            'attendance_records',
            'holidays',
            'performance_reviews',
            'payroll_runs',
            'payroll_run_lines',
            'admin_change_requests',
            'reference_cache_versions',
            'report_export_runs',
        ] as $table) {
            self::assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        self::assertTrue(Schema::hasColumns('sales', [
            'workflow_state',
            'due_date',
            'payment_terms_days',
            'rounding_adjustment_kobo',
            'fulfilment_status',
            'document_version',
            'pos_shift_id',
            'pos_terminal_id',
        ]));
        self::assertTrue(Schema::hasColumns('payment_methods', [
            'method_type',
            'is_visible_in_pos',
            'requires_reference',
            'requires_approval',
            'settlement_ledger_account_id',
        ]));
    }

    public function test_phase_six_to_nine_routes_are_registered(): void
    {
        self::assertStringContainsString('/admin/sales/workflows', route('admin.sales.workflows.index'));
        self::assertStringContainsString('/admin/pos', route('admin.pos.index'));
        self::assertStringContainsString('/admin/hr', route('admin.hr.index'));
        self::assertStringContainsString('/admin/governance/changes', route('admin.governance.changes.index'));
    }
}
