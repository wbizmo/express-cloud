<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_brandings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('business_name', 180);
            $table->string('logo_path', 500)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('email', 180)->nullable();
            $table->text('receipt_footer')->nullable();
            $table->text('document_terms')->nullable();
            $table->foreignUlid('updated_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('standalone_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('receipt_number', 40)->unique();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignUlid('payment_method_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();
            $table->foreignUlid('received_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->string('payer_name', 180);
            $table->string('payer_phone', 80)->nullable();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('reference', 180)->nullable();
            $table->string('purpose', 255);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->timestamp('received_at')->index();
            $table->timestamps();

            $table->index(['customer_id', 'received_at']);
            $table->index(['branch_id', 'received_at']);
        });

        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('return_number', 40)->unique();
            $table->foreignUlid('purchase_receipt_id')
                ->constrained('purchase_receipts')
                ->restrictOnDelete();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('processed_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->unsignedBigInteger('total_kobo');
            $table->string('supplier_credit_reference', 180)->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->text('reason');
            $table->timestamp('returned_at')->index();
            $table->timestamps();

            $table->index(['supplier_id', 'returned_at']);
            $table->index(['purchase_receipt_id', 'returned_at']);
        });

        Schema::create('purchase_return_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_return_id')
                ->constrained('purchase_returns')
                ->cascadeOnDelete();
            $table->foreignUlid('purchase_receipt_line_id')
                ->constrained('purchase_receipt_lines')
                ->restrictOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->index(['purchase_return_id', 'product_id']);
        });

        Schema::create('fixed_assets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('asset_code', 40)->unique();
            $table->string('name', 180);
            $table->string('category', 120);
            $table->foreignUlid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->foreignUlid('custodian_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->date('acquired_at')->index();
            $table->unsignedBigInteger('cost_kobo');
            $table->unsignedBigInteger('salvage_value_kobo')->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('serial_number', 160)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['category', 'status']);
        });

        Schema::create('operation_document_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('operation_type', 80)->index();
            $table->string('operation_id', 40)->index();
            $table->string('format', 20);
            $table->string('document_hash', 64);
            $table->foreignUlid('generated_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->timestamp('generated_at')->index();
            $table->timestamps();

            $table->index(
                ['operation_type', 'operation_id', 'generated_at'],
                'operation_document_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_document_logs');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('standalone_receipts');
        Schema::dropIfExists('document_brandings');
    }
};
