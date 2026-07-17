<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_imports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->string('error_report_path', 500)->nullable();
            $table->string('status', 40)->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('created_products')->default(0);
            $table->unsignedInteger('updated_products')->default(0);
            $table->unsignedInteger('created_categories')->default(0);
            $table->unsignedInteger('created_brands')->default(0);
            $table->unsignedInteger('created_suppliers')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('validated_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('product_import_rows', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_import_id')
                ->constrained('product_imports')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->json('errors')->nullable();
            $table->boolean('is_valid')->default(false)->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['product_import_id', 'row_number']);
            $table->index([
                'product_import_id',
                'is_valid',
                'row_number',
            ], 'product_import_rows_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_import_rows');
        Schema::dropIfExists('product_imports');
    }
};
