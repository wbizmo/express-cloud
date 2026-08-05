<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_postings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 191);
            $table->string('source_id', 40);
            $table->string('source_event', 80);
            $table->string('classification', 30)->index();
            $table->foreignUlid('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->restrictOnDelete();
            $table->ulid('operation_request_id')->nullable()->index();
            $table->string('reason_code', 120)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('classified_at')->index();
            $table->timestamps();

            $table->unique(
                ['source_type', 'source_id', 'source_event'],
                'financial_postings_source_event_unique',
            );
            $table->unique(
                ['journal_entry_id'],
                'financial_postings_journal_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_postings');
    }
};
