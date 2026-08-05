<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 180);
            $table->string('type', 40)->default('standard')->index();
            $table->string('status', 30)->default('active')->index();
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('allows_sales')->default(true);
            $table->boolean('allows_receipts')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'status', 'name']);
        });

        Schema::create('units_of_measure', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('dimension', 40)->default('quantity')->index();
            $table->unsignedBigInteger('conversion_numerator')->default(1);
            $table->unsignedBigInteger('conversion_denominator')->default(1);
            $table->unsignedTinyInteger('decimal_places')->default(3);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignUlid('base_unit_id')->nullable()->after('tax_rate_id')
                ->constrained('units_of_measure')->nullOnDelete();
            $table->foreignUlid('purchase_unit_id')->nullable()->after('base_unit_id')
                ->constrained('units_of_measure')->nullOnDelete();
            $table->foreignUlid('sales_unit_id')->nullable()->after('purchase_unit_id')
                ->constrained('units_of_measure')->nullOnDelete();
            $table->boolean('tracks_batches')->default(false)->after('track_inventory')->index();
            $table->boolean('tracks_serials')->default(false)->after('tracks_batches')->index();
            $table->unsignedInteger('shelf_life_days')->nullable()->after('tracks_serials');
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 120)->unique();
            $table->string('barcode', 180)->nullable()->unique();
            $table->string('name', 180);
            $table->json('attributes')->nullable();
            $table->unsignedBigInteger('price_delta_kobo')->default(0);
            $table->bigInteger('cost_delta_kobo')->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('inventory_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('batch_number', 120);
            $table->date('manufactured_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->string('status', 30)->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'product_id', 'product_variant_id', 'batch_number'],
                'inventory_batches_identity_unique',
            );
            $table->index(['warehouse_id', 'expires_on', 'status']);
        });

        Schema::create('inventory_serials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('serial_number', 180)->unique();
            $table->string('status', 30)->default('available')->index();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'status']);
        });

        Schema::create('warehouse_stock_balances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->cascadeOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->cascadeOnDelete();
            $table->string('condition', 30)->default('available')->index();
            $table->string('identity_hash', 64)->unique();
            $table->bigInteger('quantity_milliunits')->default(0);
            $table->bigInteger('reserved_milliunits')->default(0);
            $table->unsignedBigInteger('weighted_average_cost_kobo')->default(0);
            $table->unsignedBigInteger('inventory_value_kobo')->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('last_movement_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                [
                    'warehouse_id',
                    'product_id',
                    'product_variant_id',
                    'inventory_batch_id',
                    'condition',
                ],
                'warehouse_stock_balance_identity_unique',
            );
            $table->index(['warehouse_id', 'condition', 'quantity_milliunits']);
        });

        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('reference_type', 100);
            $table->string('reference_id', 64);
            $table->string('identity_hash', 64)->unique();
            $table->bigInteger('quantity_milliunits');
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['reference_type', 'reference_id', 'warehouse_id', 'product_id', 'product_variant_id'],
                'stock_reservations_reference_unique',
            );
            $table->index(['warehouse_id', 'status', 'expires_at']);
        });

        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('count_number', 50)->unique();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('opened_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status', 'created_at']);
        });

        Schema::create('stock_count_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->nullOnDelete();
            $table->string('condition', 30)->default('available');
            $table->string('identity_hash', 64)->unique();
            $table->bigInteger('system_quantity_milliunits');
            $table->bigInteger('counted_quantity_milliunits');
            $table->bigInteger('variance_milliunits');
            $table->string('reason_code', 80)->nullable();
            $table->timestamps();

            $table->unique(
                ['stock_count_id', 'product_id', 'product_variant_id', 'inventory_batch_id', 'condition'],
                'stock_count_lines_identity_unique',
            );
        });

        Schema::create('reorder_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->cascadeOnDelete();
            $table->string('identity_hash', 64)->unique();
            $table->bigInteger('reorder_point_milliunits');
            $table->bigInteger('target_stock_milliunits');
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'product_id', 'product_variant_id'],
                'reorder_rules_identity_unique',
            );
        });

        Schema::create('purchase_requisitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('requisition_number', 60)->unique();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('requested_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->date('needed_on')->nullable()->index();
            $table->text('reason');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'needed_on']);
        });

        Schema::create('purchase_requisition_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_requisition_id')
                ->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->bigInteger('requested_quantity_milliunits');
            $table->bigInteger('approved_quantity_milliunits')->default(0);
            $table->unsignedBigInteger('estimated_unit_cost_kobo')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['purchase_requisition_id', 'product_id', 'product_variant_id'],
                'purchase_requisition_lines_identity_unique',
            );
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->foreignUlid('warehouse_id')->nullable()->after('branch_id')
                ->constrained('warehouses')->nullOnDelete();
            $table->foreignUlid('purchase_requisition_id')->nullable()->after('warehouse_id')
                ->constrained('purchase_requisitions')->nullOnDelete();
            $table->string('approval_status', 30)->default('pending')->after('status')->index();
            $table->string('currency', 3)->default('NGN')->after('approval_status');
            $table->unsignedBigInteger('landed_cost_kobo')->default(0)->after('total_kobo');
            $table->timestamp('backordered_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
        });

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->foreignUlid('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            $table->bigInteger('cancelled_quantity_milliunits')->default(0)
                ->after('received_quantity_milliunits');
            $table->bigInteger('backordered_quantity_milliunits')->default(0)
                ->after('cancelled_quantity_milliunits');
            $table->unsignedBigInteger('landed_cost_allocated_kobo')->default(0)
                ->after('line_total_kobo');
        });

        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('receipt_number', 60)->unique();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignUlid('purchase_receipt_id')->nullable()
                ->constrained('purchase_receipts')->nullOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('received_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('supplier_reference', 160)->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->unsignedBigInteger('subtotal_kobo')->default(0);
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo')->default(0);
            $table->timestamp('received_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'received_at']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignUlid('purchase_order_line_id')
                ->constrained('purchase_order_lines')->restrictOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->nullOnDelete();
            $table->bigInteger('received_quantity_milliunits');
            $table->bigInteger('accepted_quantity_milliunits');
            $table->bigInteger('quarantined_quantity_milliunits')->default(0);
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->index(['goods_receipt_id', 'product_id']);
        });

        Schema::create('landed_cost_allocations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignUlid('goods_receipt_line_id')->nullable()
                ->constrained('goods_receipt_lines')->nullOnDelete();
            $table->string('cost_type', 80);
            $table->string('allocation_method', 30)->default('value');
            $table->unsignedBigInteger('amount_kobo');
            $table->foreignUlid('expense_ledger_account_id')
                ->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->foreignUlid('created_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('allocated_at')->index();
            $table->timestamps();

            $table->index(['goods_receipt_id', 'cost_type']);
        });

        Schema::create('supplier_credit_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('credit_number', 60)->unique();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUlid('supplier_bill_id')->nullable()
                ->constrained('supplier_bills')->nullOnDelete();
            $table->foreignUlid('supplier_return_id')->nullable()
                ->constrained('supplier_returns')->nullOnDelete();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUlid('created_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->unsignedBigInteger('applied_kobo')->default(0);
            $table->string('status', 30)->default('open')->index();
            $table->text('reason');
            $table->timestamp('issued_at')->index();
            $table->timestamps();

            $table->index(['supplier_id', 'status', 'issued_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignUlid('warehouse_id')->nullable()->after('branch_id')
                ->constrained('warehouses')->nullOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()->after('product_variant_id')
                ->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('inventory_serial_id')->nullable()->after('inventory_batch_id')
                ->constrained('inventory_serials')->nullOnDelete();
            $table->string('stock_condition', 30)->default('available')->after('movement_type')->index();
            $table->foreignUlid('stock_reservation_id')->nullable()
                ->after('reason_code')->constrained('stock_reservations')->nullOnDelete();
            $table->unsignedBigInteger('inventory_value_after_kobo')->default(0);
        });

        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->string('group_code', 40)->nullable()->after('type')->index();
            $table->string('normal_balance', 10)->default('debit')->after('group_code');
            $table->string('report_section', 40)->nullable()->after('normal_balance')->index();
            $table->string('cash_flow_section', 40)->nullable()->after('report_section')->index();
            $table->boolean('requires_subledger')->default(false)->after('is_control_account');
            $table->string('tax_type', 30)->nullable()->after('requires_subledger')->index();
        });

        Schema::create('accounting_close_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('accounting_period_id')
                ->constrained('accounting_periods')->restrictOnDelete();
            $table->string('status', 30)->default('preparing')->index();
            $table->json('reconciliation_snapshot');
            $table->foreignUlid('prepared_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('approved_by_account_id')->nullable()
                ->constrained('accounts')->nullOnDelete();
            $table->timestamp('prepared_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['accounting_period_id'], 'accounting_close_period_unique');
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->string('book_type', 30)->default('general')->after('status')->index();
            $table->foreignUlid('accounting_close_batch_id')->nullable()
                ->after('reversal_of_entry_id')->constrained('accounting_close_batches')->nullOnDelete();
            $table->foreignUlid('locked_by_account_id')->nullable()
                ->after('accounting_close_batch_id')->constrained('accounts')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->index();
        });

        Schema::table('journal_lines', function (Blueprint $table): void {
            $table->foreignUlid('warehouse_id')->nullable()->after('branch_id')
                ->constrained('warehouses')->nullOnDelete();
            $table->foreignUlid('tax_rate_id')->nullable()->after('supplier_id')
                ->constrained('tax_rates')->nullOnDelete();
            $table->unsignedBigInteger('tax_basis_kobo')->default(0)->after('tax_rate_id');
            $table->unsignedBigInteger('tax_amount_kobo')->default(0)->after('tax_basis_kobo');
            $table->date('due_on')->nullable()->after('tax_amount_kobo')->index();
            $table->string('subledger_reference', 120)->nullable()->after('due_on')->index();
        });

        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 160);
            $table->string('bank_name', 160);
            $table->string('account_number_masked', 40);
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->unique(['ledger_account_id']);
        });

        Schema::create('bank_statement_imports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->foreignUlid('imported_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedBigInteger('opening_balance_kobo');
            $table->unsignedBigInteger('closing_balance_kobo');
            $table->string('file_hash', 64)->unique();
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('imported_at');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'starts_on', 'ends_on']);
        });

        Schema::create('bank_statement_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('bank_statement_import_id')
                ->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->date('transaction_date')->index();
            $table->string('reference', 160)->nullable()->index();
            $table->string('description', 500);
            $table->unsignedBigInteger('debit_kobo')->default(0);
            $table->unsignedBigInteger('credit_kobo')->default(0);
            $table->unsignedBigInteger('running_balance_kobo');
            $table->string('status', 30)->default('unmatched')->index();
            $table->timestamps();

            $table->index(['bank_statement_import_id', 'status', 'transaction_date']);
        });

        Schema::create('bank_reconciliation_matches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('bank_statement_line_id')
                ->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->foreignUlid('journal_line_id')->constrained('journal_lines')->restrictOnDelete();
            $table->foreignUlid('matched_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedBigInteger('matched_amount_kobo');
            $table->timestamp('matched_at')->index();
            $table->timestamps();

            $table->unique(['bank_statement_line_id', 'journal_line_id'], 'bank_reconciliation_pair_unique');
        });

        Schema::create('treasury_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 160);
            $table->string('type', 30)->index();
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['ledger_account_id', 'branch_id', 'type'], 'treasury_account_identity_unique');
        });

        Schema::create('treasury_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('movement_number', 60)->unique();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('source_treasury_account_id')
                ->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->foreignUlid('destination_treasury_account_id')
                ->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUlid('created_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignUlid('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->string('movement_type', 30)->index();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('reference', 160)->nullable();
            $table->text('memo');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('accrual_schedules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('schedule_number', 60)->unique();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUlid('operation_request_id')->nullable()->unique()
                ->constrained('operation_requests')->nullOnDelete();
            $table->foreignUlid('expense_ledger_account_id')
                ->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignUlid('balance_sheet_ledger_account_id')
                ->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignUlid('created_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('schedule_type', 30)->index();
            $table->unsignedBigInteger('total_kobo');
            $table->unsignedInteger('period_count');
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->unsignedInteger('posted_periods')->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->text('memo');
            $table->timestamps();
        });

        Schema::create('accrual_postings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('accrual_schedule_id')
                ->constrained('accrual_schedules')->cascadeOnDelete();
            $table->foreignUlid('journal_entry_id')
                ->constrained('journal_entries')->restrictOnDelete();
            $table->unsignedInteger('period_number');
            $table->date('posting_date')->index();
            $table->unsignedBigInteger('amount_kobo');
            $table->timestamps();

            $table->unique(
                ['accrual_schedule_id', 'period_number'],
                'accrual_posting_period_unique',
            );
        });

        Schema::create('cash_counters', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUlid('treasury_account_id')
                ->constrained('treasury_accounts')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('cash_counter_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('cash_counter_id')
                ->constrained('cash_counters')->restrictOnDelete();
            $table->foreignUlid('treasury_movement_id')->nullable()
                ->constrained('treasury_movements')->nullOnDelete();
            $table->foreignUlid('recorded_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->string('movement_type', 30)->index();
            $table->unsignedBigInteger('amount_kobo');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('supplier_credit_applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_credit_note_id')
                ->constrained('supplier_credit_notes')->restrictOnDelete();
            $table->foreignUlid('supplier_bill_id')
                ->constrained('supplier_bills')->restrictOnDelete();
            $table->foreignUlid('applied_by_account_id')
                ->constrained('accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->timestamp('applied_at')->index();
            $table->timestamps();

            $table->unique(
                ['supplier_credit_note_id', 'supplier_bill_id'],
                'supplier_credit_application_unique',
            );
        });

        Schema::create('asset_disposals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('fixed_asset_id')->constrained('fixed_assets')->restrictOnDelete();
            $table->foreignUlid('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->foreignUlid('disposed_by_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('disposed_on')->index();
            $table->unsignedBigInteger('proceeds_kobo')->default(0);
            $table->unsignedBigInteger('net_book_value_kobo');
            $table->bigInteger('gain_loss_kobo');
            $table->string('method', 40);
            $table->string('reference', 160)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fixed_asset_id']);
        });

        Schema::create('inventory_valuation_snapshots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('snapshot_date')->index();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUlid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUlid('inventory_batch_id')->nullable()
                ->constrained('inventory_batches')->nullOnDelete();
            $table->string('condition', 30);
            $table->string('identity_hash', 64);
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('weighted_average_cost_kobo');
            $table->unsignedBigInteger('inventory_value_kobo');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(
                ['snapshot_date', 'identity_hash'],
                'inventory_valuation_snapshot_identity_unique',
            );
        });

        $now = now();
        $branches = DB::table('branches')->orderBy('id')->get(['id', 'name', 'code']);
        foreach ($branches as $branch) {
            $warehouseId = Str::ulid()->toString();
            DB::table('warehouses')->insertOrIgnore([
                'id' => $warehouseId,
                'branch_id' => $branch->id,
                'code' => 'WH-'.($branch->code ?: substr((string) $branch->id, -6)),
                'name' => $branch->name.' Main Warehouse',
                'type' => 'standard',
                'status' => 'active',
                'is_default' => true,
                'allows_sales' => true,
                'allows_receipts' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('units_of_measure')->insertOrIgnore([
            [
                'id' => Str::ulid()->toString(),
                'code' => 'EA',
                'name' => 'Each',
                'dimension' => 'quantity',
                'conversion_numerator' => 1,
                'conversion_denominator' => 1,
                'decimal_places' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $defaultUnitId = DB::table('units_of_measure')->where('code', 'EA')->value('id');
        if (is_string($defaultUnitId)) {
            DB::table('products')->whereNull('base_unit_id')->update([
                'base_unit_id' => $defaultUnitId,
                'purchase_unit_id' => $defaultUnitId,
                'sales_unit_id' => $defaultUnitId,
            ]);
        }

        $stocks = DB::table('product_branch_stock')->orderBy('id')->get();
        foreach ($stocks as $stock) {
            $warehouseId = DB::table('warehouses')
                ->where('branch_id', $stock->branch_id)
                ->where('is_default', true)
                ->value('id');
            if (! is_string($warehouseId)) {
                continue;
            }

            $cost = (int) (DB::table('products')
                ->where('id', $stock->product_id)
                ->value('default_cost_price_kobo') ?? 0);
            $value = (int) round(((int) $stock->quantity_milliunits / 1000) * $cost);

            DB::table('warehouse_stock_balances')->insertOrIgnore([
                'id' => Str::ulid()->toString(),
                'warehouse_id' => $warehouseId,
                'product_id' => $stock->product_id,
                'product_variant_id' => null,
                'inventory_batch_id' => null,
                'condition' => 'available',
                'identity_hash' => hash('sha256', $warehouseId.'|'.$stock->product_id.'|||available'),
                'quantity_milliunits' => $stock->quantity_milliunits,
                'reserved_milliunits' => 0,
                'weighted_average_cost_kobo' => $cost,
                'inventory_value_kobo' => max(0, $value),
                'version' => 1,
                'last_movement_at' => $stock->last_movement_at,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_snapshots');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('supplier_credit_applications');
        Schema::dropIfExists('cash_counter_movements');
        Schema::dropIfExists('cash_counters');
        Schema::dropIfExists('accrual_postings');
        Schema::dropIfExists('accrual_schedules');
        Schema::dropIfExists('treasury_movements');
        Schema::dropIfExists('treasury_accounts');
        Schema::dropIfExists('bank_reconciliation_matches');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_accounts');

        Schema::table('journal_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropColumn([
                'tax_basis_kobo',
                'tax_amount_kobo',
                'due_on',
                'subledger_reference',
            ]);
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('accounting_close_batch_id');
            $table->dropConstrainedForeignId('locked_by_account_id');
            $table->dropColumn(['book_type', 'locked_at']);
        });

        Schema::dropIfExists('accounting_close_batches');

        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'group_code',
                'normal_balance',
                'report_section',
                'cash_flow_section',
                'requires_subledger',
                'tax_type',
            ]);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropConstrainedForeignId('inventory_batch_id');
            $table->dropConstrainedForeignId('inventory_serial_id');
            $table->dropConstrainedForeignId('stock_reservation_id');
            $table->dropColumn(['stock_condition', 'inventory_value_after_kobo']);
        });

        Schema::dropIfExists('supplier_credit_notes');
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn([
                'cancelled_quantity_milliunits',
                'backordered_quantity_milliunits',
                'landed_cost_allocated_kobo',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('purchase_requisition_id');
            $table->dropColumn([
                'approval_status',
                'currency',
                'landed_cost_kobo',
                'backordered_at',
                'closed_at',
            ]);
        });

        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('warehouse_stock_balances');
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('base_unit_id');
            $table->dropConstrainedForeignId('purchase_unit_id');
            $table->dropConstrainedForeignId('sales_unit_id');
            $table->dropColumn(['tracks_batches', 'tracks_serials', 'shelf_life_days']);
        });

        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('warehouses');
    }
};
