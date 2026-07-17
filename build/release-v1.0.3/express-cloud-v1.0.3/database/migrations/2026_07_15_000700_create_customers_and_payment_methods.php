<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('customer_code', 60)->unique();
            $table->string('name', 150);
            $table->string('phone', 30)->index();
            $table->text('email_encrypted')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('credit_limit_kobo')->default(0);
            $table->bigInteger('balance_kobo')->default(0);
            $table->boolean('is_wholesale')->default(false)->index();
            $table->string('status', 30)->default('active')->index();
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index(['phone', 'status']);
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 80);
            $table->text('account_number_encrypted')->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_default')->default(false)->index();
            $table->boolean('is_default_for_pos')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique('name');
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('customers');
    }
};
