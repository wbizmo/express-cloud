<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('default_payment_terms_days')->default(0);
            $table->unsignedBigInteger('default_credit_limit_kobo')->default(0);
            $table->string('price_group', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignUlid('customer_group_id')->nullable()
                ->constrained('customer_groups')->nullOnDelete();
            $table->string('customer_type', 20)->default('individual')->index();
            $table->string('contact_person', 160)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('tax_number', 100)->nullable()->index();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->string('price_group', 80)->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignUlid('archived_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->index(
                ['customer_group_id', 'status', 'name'],
                'customers_group_status_name_idx',
            );
        });

        Schema::create('commercial_approval_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('request_type', 80)->index();
            $table->string('subject_type', 160)->index();
            $table->string('subject_id', 64)->index();
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->foreignUlid('requested_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('decided_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->json('requested_changes');
            $table->text('business_memo');
            $table->string('status', 30)->default('pending')->index();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable()->index();
            $table->timestamps();
            $table->index(
                ['subject_type', 'subject_id', 'status'],
                'commercial_approval_subject_idx',
            );
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->string('workflow_state', 30)->default('draft')->index();
            $table->date('due_date')->nullable()->index();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->bigInteger('rounding_adjustment_kobo')->default(0);
            $table->string('fulfilment_status', 30)->default('not_required')->index();
            $table->unsignedInteger('document_version')->default(1);
            $table->foreignUlid('discount_approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('price_override_approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('commercial_approval_request_id')->nullable()
                ->constrained('commercial_approval_requests')->nullOnDelete();
            $table->text('approval_memo')->nullable();
            $table->index(
                ['branch_id', 'sale_type', 'workflow_state', 'sale_date'],
                'sales_branch_type_workflow_date_idx',
            );
            $table->index(
                ['customer_id', 'due_date', 'status'],
                'sales_customer_due_status_idx',
            );
        });

        Schema::create('sales_document_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUlid('account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('event_type', 80)->index();
            $table->string('from_state', 30)->nullable();
            $table->string('to_state', 30)->nullable();
            $table->json('details')->nullable();
            $table->text('memo')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['sale_id', 'occurred_at'], 'sales_document_events_sale_idx');
        });

        Schema::create('sales_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('delivery_number', 60)->unique();
            $table->foreignUlid('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignUlid('warehouse_id')->nullable()
                ->constrained('warehouses')->nullOnDelete();
            $table->foreignUlid('delivered_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->string('status', 30)->default('dispatched')->index();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
            $table->index(['sale_id', 'status'], 'sales_deliveries_sale_status_idx');
        });

        Schema::create('sales_delivery_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_delivery_id')
                ->constrained('sales_deliveries')->cascadeOnDelete();
            $table->foreignUlid('sale_item_id')
                ->constrained('sale_items')->restrictOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->timestamps();
            $table->unique(
                ['sales_delivery_id', 'sale_item_id'],
                'sales_delivery_lines_identity_unique',
            );
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('method_type', 40)->default('cash')->index();
            $table->boolean('is_visible_in_pos')->default(true)->index();
            $table->boolean('is_visible_in_commerce')->default(false)->index();
            $table->boolean('requires_reference')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->foreignUlid('settlement_ledger_account_id')->nullable()
                ->constrained('ledger_accounts')->nullOnDelete();
            $table->text('instructions')->nullable();
        });

        Schema::create('pos_terminals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('device_fingerprint_hash', 64)->nullable()->index();
            $table->string('printer_profile', 30)->default('80mm');
            $table->string('status', 30)->default('active')->index();
            $table->foreignUlid('assigned_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
            $table->index(['branch_id', 'status'], 'pos_terminals_branch_status_idx');
        });

        Schema::create('pos_shifts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('shift_number', 60)->unique();
            $table->foreignUlid('pos_terminal_id')
                ->constrained('pos_terminals')->restrictOnDelete();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUlid('cashier_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('closed_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('status', 20)->default('open')->index();
            $table->unsignedBigInteger('opening_float_kobo')->default(0);
            $table->bigInteger('expected_cash_kobo')->default(0);
            $table->bigInteger('declared_cash_kobo')->default(0);
            $table->bigInteger('cash_variance_kobo')->default(0);
            $table->timestamp('opened_at')->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->text('closing_note')->nullable();
            $table->timestamps();
            $table->index(
                ['branch_id', 'cashier_account_id', 'status'],
                'pos_shifts_branch_cashier_status_idx',
            );
        });

        Schema::create('pos_shift_tenders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('pos_shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $table->foreignUlid('payment_method_id')
                ->constrained('payment_methods')->restrictOnDelete();
            $table->bigInteger('expected_amount_kobo')->default(0);
            $table->bigInteger('counted_amount_kobo')->default(0);
            $table->bigInteger('variance_kobo')->default(0);
            $table->timestamps();
            $table->unique(
                ['pos_shift_id', 'payment_method_id'],
                'pos_shift_tenders_identity_unique',
            );
        });

        Schema::create('pos_cash_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('pos_shift_id')->constrained('pos_shifts')->restrictOnDelete();
            $table->foreignUlid('recorded_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->string('movement_type', 30)->index();
            $table->bigInteger('amount_kobo');
            $table->text('memo');
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
            $table->index(['pos_shift_id', 'recorded_at'], 'pos_cash_movements_shift_idx');
        });

        Schema::create('pos_held_sales', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('hold_token', 80)->unique();
            $table->foreignUlid('pos_shift_id')->constrained('pos_shifts')->restrictOnDelete();
            $table->foreignUlid('customer_id')->nullable()
                ->constrained('customers')->nullOnDelete();
            $table->foreignUlid('held_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->json('cart_payload');
            $table->unsignedBigInteger('estimated_total_kobo')->default(0);
            $table->string('status', 20)->default('held')->index();
            $table->timestamp('held_at')->index();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();
            $table->index(['pos_shift_id', 'status', 'held_at'], 'pos_held_sales_shift_idx');
        });

        Schema::create('pos_receipt_prints', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignUlid('pos_shift_id')->nullable()
                ->constrained('pos_shifts')->nullOnDelete();
            $table->foreignUlid('printed_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('approval_request_id')->nullable()
                ->constrained('commercial_approval_requests')->nullOnDelete();
            $table->string('format', 20)->default('80mm');
            $table->unsignedSmallInteger('copy_number')->default(1);
            $table->boolean('is_reprint')->default(false)->index();
            $table->text('reason')->nullable();
            $table->timestamp('printed_at')->index();
            $table->timestamps();
            $table->unique(['sale_id', 'copy_number'], 'pos_receipt_prints_sale_copy_unique');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignUlid('pos_shift_id')->nullable()
                ->constrained('pos_shifts')->nullOnDelete();
            $table->foreignUlid('pos_terminal_id')->nullable()
                ->constrained('pos_terminals')->nullOnDelete();
            $table->index(['pos_shift_id', 'created_at'], 'sales_pos_shift_created_idx');
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->foreignUlid('manager_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['branch_id', 'status', 'name'], 'departments_branch_status_idx');
        });

        Schema::create('job_roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('title', 140);
            $table->foreignUlid('department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('employee_code', 50)->unique();
            $table->foreignUlid('account_id')->nullable()->unique()
                ->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->foreignUlid('department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->foreignUlid('job_role_id')->nullable()
                ->constrained('job_roles')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->text('email_encrypted')->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('hired_on')->nullable();
            $table->date('terminated_on')->nullable();
            $table->string('employment_type', 30)->default('full_time')->index();
            $table->string('status', 20)->default('active')->index();
            $table->foreignUlid('created_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'department_id', 'status'], 'employees_org_status_idx');
            $table->index(['status', 'last_name', 'first_name'], 'employees_status_name_idx');
        });

        Schema::create('employee_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->foreignUlid('department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->foreignUlid('job_role_id')->nullable()
                ->constrained('job_roles')->nullOnDelete();
            $table->date('starts_on')->index();
            $table->date('ends_on')->nullable()->index();
            $table->foreignUlid('assigned_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->text('memo')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'starts_on'], 'employee_assignments_employee_idx');
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->date('work_date')->index();
            $table->timestamp('clocked_in_at')->nullable();
            $table->timestamp('clocked_out_at')->nullable();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->string('status', 20)->default('present')->index();
            $table->foreignUlid('recorded_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date'], 'attendance_employee_date_unique');
            $table->index(['branch_id', 'work_date', 'status'], 'attendance_branch_date_idx');
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 160);
            $table->date('holiday_date')->index();
            $table->foreignUlid('branch_id')->nullable()
                ->constrained('branches')->nullOnDelete();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique(['branch_id', 'holiday_date'], 'holidays_branch_date_unique');
        });

        Schema::create('performance_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUlid('reviewer_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->date('period_starts_on');
            $table->date('period_ends_on')->index();
            $table->unsignedTinyInteger('score');
            $table->json('metrics')->nullable();
            $table->text('summary');
            $table->text('development_plan')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'period_ends_on'], 'performance_reviews_employee_idx');
        });

        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('run_number', 60)->unique();
            $table->date('period_starts_on');
            $table->date('period_ends_on')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignUlid('prepared_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->unsignedBigInteger('gross_total_kobo')->default(0);
            $table->unsignedBigInteger('deduction_total_kobo')->default(0);
            $table->unsignedBigInteger('net_total_kobo')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_run_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->unsignedBigInteger('gross_kobo');
            $table->unsignedBigInteger('deductions_kobo')->default(0);
            $table->unsignedBigInteger('net_kobo');
            $table->json('components')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id'], 'payroll_run_employee_unique');
        });

        Schema::create('admin_change_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('resource_type', 160)->index();
            $table->string('resource_id', 64)->nullable()->index();
            $table->string('action', 30)->index();
            $table->json('payload');
            $table->foreignUlid('requested_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('decided_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->text('business_memo');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['resource_type', 'status', 'created_at'], 'admin_change_resource_status_idx');
        });

        Schema::create('reference_cache_versions', function (Blueprint $table): void {
            $table->string('namespace', 120)->primary();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_export_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('report_key', 120)->index();
            $table->foreignUlid('requested_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->json('filters')->nullable();
            $table->string('format', 20)->default('csv');
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedBigInteger('row_count')->default(0);
            $table->string('storage_path', 500)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'report_export_runs_status_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['branch_id', 'occurred_at', 'id'], 'stock_movements_branch_cursor_idx');
        });
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['created_at', 'id'], 'audit_logs_cursor_idx');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['payment_method_id', 'paid_at', 'id'], 'payments_method_cursor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table): mixed => $table->dropIndex('payments_method_cursor_idx'));
        Schema::table('audit_logs', fn (Blueprint $table): mixed => $table->dropIndex('audit_logs_cursor_idx'));
        Schema::table('stock_movements', fn (Blueprint $table): mixed => $table->dropIndex('stock_movements_branch_cursor_idx'));
        Schema::dropIfExists('report_export_runs');
        Schema::dropIfExists('reference_cache_versions');
        Schema::dropIfExists('admin_change_requests');
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_assignments');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('job_roles');
        Schema::dropIfExists('departments');
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('sales_pos_shift_created_idx');
            $table->dropConstrainedForeignId('pos_terminal_id');
            $table->dropConstrainedForeignId('pos_shift_id');
        });
        Schema::dropIfExists('pos_receipt_prints');
        Schema::dropIfExists('pos_held_sales');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_shift_tenders');
        Schema::dropIfExists('pos_shifts');
        Schema::dropIfExists('pos_terminals');
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('settlement_ledger_account_id');
            $table->dropColumn([
                'method_type', 'is_visible_in_pos', 'is_visible_in_commerce',
                'requires_reference', 'requires_approval', 'instructions',
            ]);
        });
        Schema::dropIfExists('sales_delivery_lines');
        Schema::dropIfExists('sales_deliveries');
        Schema::dropIfExists('sales_document_events');
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('sales_branch_type_workflow_date_idx');
            $table->dropIndex('sales_customer_due_status_idx');
            $table->dropConstrainedForeignId('commercial_approval_request_id');
            $table->dropConstrainedForeignId('price_override_approved_by_account_id');
            $table->dropConstrainedForeignId('discount_approved_by_account_id');
            $table->dropColumn([
                'workflow_state', 'due_date', 'payment_terms_days',
                'rounding_adjustment_kobo', 'fulfilment_status',
                'document_version', 'approval_memo',
            ]);
        });
        Schema::dropIfExists('commercial_approval_requests');
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_group_status_name_idx');
            $table->dropConstrainedForeignId('archived_by_account_id');
            $table->dropConstrainedForeignId('customer_group_id');
            $table->dropColumn([
                'customer_type', 'contact_person', 'billing_address',
                'shipping_address', 'tax_number', 'payment_terms_days',
                'price_group', 'archived_at',
            ]);
        });
        Schema::dropIfExists('customer_groups');
    }
};
