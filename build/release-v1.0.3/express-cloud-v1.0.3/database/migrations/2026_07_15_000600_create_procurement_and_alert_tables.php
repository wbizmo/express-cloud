<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('order_number', 80)->unique();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->foreignUlid('approved_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('status', 40)->index();
            $table->date('expected_at')->nullable()->index();
            $table->unsignedBigInteger('subtotal_kobo')->default(0);
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo')->default(0);
            $table->text('reference_note');
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->bigInteger('ordered_quantity_milliunits');
            $table->bigInteger('received_quantity_milliunits')->default(0);
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedInteger('tax_rate_basis_points')->default(0);
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->unique(['purchase_order_id', 'product_id']);
        });

        Schema::create('low_stock_alerts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->bigInteger('minimum_stock_milliunits');
            $table->timestamp('opened_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['branch_id', 'resolved_at', 'opened_at'],
                'low_stock_alerts_branch_open_index',
            );
            $table->unique(
                ['product_id', 'branch_id', 'resolved_at'],
                'low_stock_alerts_resolution_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
