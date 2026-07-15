<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_vouchers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('value_type', 30);
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('minimum_sale_kobo')->default(0);
            $table->unsignedBigInteger('maximum_discount_kobo')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->foreignUlid('created_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('voucher_redemptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('discount_voucher_id')
                ->constrained('discount_vouchers')
                ->restrictOnDelete();
            $table->foreignUlid('sale_id')
                ->constrained('sales')
                ->restrictOnDelete();
            $table->foreignUlid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignUlid('redeemed_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->unsignedBigInteger('discount_amount_kobo');
            $table->timestamp('redeemed_at')->index();
            $table->timestamps();

            $table->unique(['discount_voucher_id', 'sale_id']);
            $table->index(['customer_id', 'redeemed_at']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignUlid('discount_voucher_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('discount_vouchers')
                ->nullOnDelete();
            $table->string('credit_note', 255)
                ->nullable()
                ->after('notes');
        });

        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('return_code', 40)->unique();
            $table->foreignUlid('sale_id')
                ->constrained('sales')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignUlid('processed_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->unsignedBigInteger('total_refund_kobo');
            $table->string('refund_method', 80)->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->text('reason');
            $table->timestamp('returned_at')->index();
            $table->timestamps();

            $table->index(['sale_id', 'returned_at']);
        });

        Schema::create('sale_return_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_return_id')
                ->constrained('sale_returns')
                ->cascadeOnDelete();
            $table->foreignUlid('sale_item_id')
                ->constrained('sale_items')
                ->restrictOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('refund_amount_kobo');
            $table->boolean('restock')->default(true);
            $table->timestamps();

            $table->index(['sale_return_id', 'product_id']);
        });

        Schema::create('purchase_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('receipt_number', 40)->unique();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('recorded_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->foreignUlid('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->date('purchased_at')->index();
            $table->string('supplier_reference', 160)->nullable();
            $table->unsignedBigInteger('subtotal_kobo');
            $table->unsignedBigInteger('discount_kobo')->default(0);
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo');
            $table->string('status', 30)->default('recorded')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'purchased_at']);
            $table->index(['branch_id', 'purchased_at']);
        });

        Schema::create('purchase_receipt_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_receipt_id')
                ->constrained('purchase_receipts')
                ->cascadeOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedBigInteger('discount_kobo')->default(0);
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->index(['purchase_receipt_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('discount_voucher_id');
            $table->dropColumn('credit_note');
        });

        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('discount_vouchers');
    }
};
