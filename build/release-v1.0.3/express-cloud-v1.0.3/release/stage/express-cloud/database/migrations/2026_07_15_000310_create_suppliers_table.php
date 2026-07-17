<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('supplier_code', 60)->unique();
            $table->string('company_name', 180);
            $table->string('contact_person', 160)->nullable();
            $table->string('category', 120)->nullable()->index();
            $table->text('email_encrypted')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->text('tax_number_encrypted')->nullable();
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->unsignedBigInteger('credit_limit_kobo')->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->text('delivery_terms')->nullable();
            $table->text('return_policy')->nullable();
            $table->boolean('is_preferred')->default(false)->index();
            $table->string('status', 32)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'company_name']);
            $table->index(['is_preferred', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
