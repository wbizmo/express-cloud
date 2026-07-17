<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_branch_stock', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->bigInteger('quantity_milliunits')->default(0);
            $table->bigInteger('minimum_stock_milliunits')->default(5000);
            $table->timestamp('last_movement_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
            $table->index([
                'branch_id',
                'quantity_milliunits',
                'minimum_stock_milliunits',
            ], 'product_branch_stock_low_index');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('movement_type', 40)->index();
            $table->bigInteger('quantity_delta_milliunits');
            $table->bigInteger('balance_after_milliunits');
            $table->unsignedBigInteger('unit_cost_kobo')->nullable();
            $table->string('reference_type', 60)->nullable()->index();
            $table->string('reference_id', 64)->nullable()->index();
            $table->ulid('correlation_id')->nullable()->index();
            $table->string('reason_code', 60)->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['product_id', 'branch_id', 'occurred_at'],
                'stock_movements_product_branch_time_index',
            );
            $table->index(
                ['reference_type', 'reference_id'],
                'stock_movements_reference_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_branch_stock');
    }
};
