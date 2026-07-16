<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 200);
            $table->string('sku', 100)->unique();
            $table->string('barcode', 160)->nullable()->unique();
            $table->foreignUlid('category_id')
                ->constrained('product_categories')
                ->restrictOnDelete();
            $table->foreignUlid('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();
            $table->foreignUlid('tax_rate_id')
                ->nullable()
                ->constrained('tax_rates')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->boolean('track_inventory')->default(true)->index();
            $table->unsignedBigInteger('default_price_kobo');
            $table->unsignedBigInteger('default_cost_price_kobo')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
        });

        Schema::create('product_branch_prices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('price_kobo');
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
            $table->index(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_prices');
        Schema::dropIfExists('products');
    }
};
