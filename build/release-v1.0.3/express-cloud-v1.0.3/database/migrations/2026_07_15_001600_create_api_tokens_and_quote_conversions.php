<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 120);
            $table->string('token_prefix', 16)->index();
            $table->string('token_hash', 64)->unique();
            $table->json('abilities');
            $table->foreignUlid('created_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['revoked_at', 'expires_at'],
                'api_tokens_validity_index',
            );
        });

        Schema::create('quote_conversions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('source_quote_id')
                ->constrained('sales')
                ->restrictOnDelete();
            $table->foreignUlid('converted_sale_id')
                ->constrained('sales')
                ->restrictOnDelete();
            $table->foreignUlid('converted_by_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->string('target_type', 20);
            $table->timestamp('converted_at')->index();
            $table->timestamps();

            $table->unique(
                ['source_quote_id', 'converted_sale_id'],
                'quote_conversion_pair_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_conversions');
        Schema::dropIfExists('api_tokens');
    }
};
