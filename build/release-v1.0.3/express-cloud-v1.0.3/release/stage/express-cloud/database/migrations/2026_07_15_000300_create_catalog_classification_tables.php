<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 140);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->index(['status', 'name']);
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 140);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->index(['status', 'name']);
        });

        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 120);
            $table->unsignedInteger('rate_basis_points')->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('product_categories');
    }
};
