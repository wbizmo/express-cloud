<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Only add the column if it doesn't already exist
        if (! Schema::hasColumn('sale_items', 'unit_cost_kobo_snapshot')) {
            Schema::table('sale_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('unit_cost_kobo_snapshot')
                    ->default(0)
                    ->after('unit_price_kobo');
            });
        }

        // Update existing rows using the appropriate syntax for each driver
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE sale_items SET unit_cost_kobo_snapshot = (SELECT default_cost_price_kobo FROM products WHERE products.id = sale_items.product_id) WHERE unit_cost_kobo_snapshot = 0');
        } else {
            DB::statement('UPDATE sale_items si JOIN products p ON p.id = si.product_id SET si.unit_cost_kobo_snapshot = p.default_cost_price_kobo WHERE si.unit_cost_kobo_snapshot = 0');
        }

        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 180);
            $table->string('type', 30)->index();
            $table->foreignUlid('parent_id')
                ->nullable()
                ->constrained('ledger_accounts')
                ->nullOnDelete();
            $table->boolean('is_control_account')->default(false)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('allow_manual_posting')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active', 'code']);
        });

        Schema::create('accounting_periods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 120);
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->string('status', 30)->default('open')->index();
            $table->foreignUlid('closed_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignUlid('locked_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['starts_on', 'ends_on']);
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('journal_number', 50)->unique();
            $table->date('entry_date')->index();
            $table->foreignUlid('accounting_period_id')
                ->constrained('accounting_periods')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->string('source_type', 120)->nullable()->index();
            $table->string('source_id', 40)->nullable()->index();
            $table->string('source_event', 80)->nullable();
            $table->string('status', 30)->default('posted')->index();
            $table->string('memo', 500);
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->foreignUlid('reversal_of_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('reversed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['source_type', 'source_id', 'source_event'],
                'journal_source_event_unique',
            );
            $table->index(['entry_date', 'status']);
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();
            $table->foreignUlid('ledger_account_id')
                ->constrained('ledger_accounts')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->foreignUlid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignUlid('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->unsignedBigInteger('debit_kobo')->default(0);
            $table->unsignedBigInteger('credit_kobo')->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['ledger_account_id', 'journal_entry_id']);
            $table->index(['customer_id', 'ledger_account_id']);
            $table->index(['supplier_id', 'ledger_account_id']);
        });

        Schema::create('asset_depreciation_postings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('fixed_asset_id')
                ->constrained('fixed_assets')
                ->restrictOnDelete();
            $table->foreignUlid('journal_entry_id')
                ->constrained('journal_entries')
                ->restrictOnDelete();
            $table->date('period_end')->index();
            $table->unsignedBigInteger('amount_kobo');
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'period_end']);
        });
    }

    public function down(): void
    {
        // Drop the column if it exists (to be clean)
        if (Schema::hasColumn('sale_items', 'unit_cost_kobo_snapshot')) {
            Schema::table('sale_items', function (Blueprint $table): void {
                $table->dropColumn('unit_cost_kobo_snapshot');
            });
        }

        Schema::dropIfExists('asset_depreciation_postings');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('ledger_accounts');
    }
};
