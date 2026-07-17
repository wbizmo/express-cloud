<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_bills', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('bill_number', 80)->unique();
            $table->string('supplier_reference', 160)->nullable()->index();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->date('bill_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->unsignedBigInteger('subtotal_kobo');
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('total_kobo');
            $table->unsignedBigInteger('paid_kobo')->default(0);
            $table->string('status', 40)->index();
            $table->text('reference_note');
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamps();

            $table->index(['supplier_id', 'status', 'due_date']);
            $table->index(['branch_id', 'bill_date']);
        });

        Schema::create('supplier_bill_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_bill_id')
                ->constrained('supplier_bills')
                ->cascadeOnDelete();
            $table->foreignUlid('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
            $table->string('description', 255);
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedInteger('tax_rate_basis_points')->default(0);
            $table->unsignedBigInteger('line_subtotal_kobo');
            $table->unsignedBigInteger('tax_kobo')->default(0);
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->index(['supplier_bill_id', 'product_id']);
        });

        Schema::create('supplier_bill_payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_bill_id')
                ->constrained('supplier_bills')
                ->cascadeOnDelete();
            $table->foreignUlid('payment_method_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();
            $table->foreignUlid('recorded_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('reference', 160)->nullable();
            $table->timestamp('paid_at')->index();
            $table->timestamps();

            $table->index(['supplier_bill_id', 'paid_at']);
        });

        Schema::create('supplier_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('return_number', 80)->unique();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->foreignUlid('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignUlid('supplier_bill_id')
                ->nullable()
                ->constrained('supplier_bills')
                ->nullOnDelete();
            $table->foreignUlid('created_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('status', 40)->index();
            $table->date('return_date')->index();
            $table->unsignedBigInteger('total_kobo')->default(0);
            $table->string('reason', 120);
            $table->text('reference_note');
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['supplier_id', 'return_date']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('supplier_return_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_return_id')
                ->constrained('supplier_returns')
                ->cascadeOnDelete();
            $table->foreignUlid('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->bigInteger('quantity_milliunits');
            $table->unsignedBigInteger('unit_cost_kobo');
            $table->unsignedBigInteger('line_total_kobo');
            $table->timestamps();

            $table->unique(['supplier_return_id', 'product_id']);
        });

        Schema::create('supplier_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();
            $table->foreignUlid('supplier_bill_id')
                ->nullable()
                ->constrained('supplier_bills')
                ->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 160);
            $table->unsignedBigInteger('size_bytes');
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'created_at']);
            $table->index(['supplier_bill_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_return_lines');
        Schema::dropIfExists('supplier_returns');
        Schema::dropIfExists('supplier_bill_payments');
        Schema::dropIfExists('supplier_bill_lines');
        Schema::dropIfExists('supplier_bills');
    }
};
