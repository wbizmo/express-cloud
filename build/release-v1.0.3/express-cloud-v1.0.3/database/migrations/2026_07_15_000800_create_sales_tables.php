<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('sale_code', 40)->unique();
            $table->string('sale_type', 30)->index();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignUlid('sold_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->foreignUlid('converted_from_sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete();
            $table->date('sale_date')->index();
            $table->unsignedBigInteger('subtotal_kobo')->default(0);
            $table->unsignedBigInteger('discount_amount_kobo')->default(0);
            $table->unsignedBigInteger('tax_amount_kobo')->default(0);
            $table->unsignedBigInteger('grand_total_kobo')->default(0);
            $table->unsignedBigInteger('paid_amount_kobo')->default(0);
            $table->string('status', 30)->index();
            $table->string('idempotency_key', 80)->unique();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['branch_id', 'sale_date']);
            $table->index(['sold_by_account_id', 'sale_date']);
            $table->index(['sale_type', 'status', 'sale_date']);
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->string('product_name_snapshot', 200);
            $table->string('sku_snapshot', 100);
            $table->boolean('track_inventory_snapshot');
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('unit_price_kobo');
            $table->unsignedBigInteger('discount_amount_kobo')->default(0);
            $table->unsignedBigInteger('tax_amount_kobo')->default(0);
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->index(['sale_id', 'product_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();
            $table->foreignUlid('payment_method_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->foreignUlid('recorded_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->string('reference', 160)->nullable();
            $table->timestamp('paid_at')->index();
            $table->timestamps();

            $table->index(['sale_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
