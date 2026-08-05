<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('scope', 80);
            $table->string('idempotency_key', 120);
            $table->char('request_fingerprint', 64);
            $table->ulid('account_id')->nullable()->index();
            $table->ulid('branch_id')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('result_type', 191)->nullable();
            $table->ulid('result_id')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_code', 191)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['scope', 'idempotency_key'],
                'operation_requests_scope_key_unique',
            );
            $table->index(
                ['account_id', 'created_at'],
                'operation_requests_account_created_index',
            );
        });

        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('operation_request_id')->index();
            $table->string('event_type', 120);
            $table->string('aggregate_type', 191);
            $table->ulid('aggregate_id');
            $table->json('payload');
            $table->timestamp('occurred_at')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(
                [
                    'operation_request_id',
                    'event_type',
                    'aggregate_type',
                    'aggregate_id',
                ],
                'outbox_events_operation_event_aggregate_unique',
            );
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->ulid('operation_request_id')->nullable()->unique();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->ulid('operation_request_id')->nullable()->index();
            $table->unsignedSmallInteger('operation_sequence')->nullable();
            $table->unique(
                ['operation_request_id', 'operation_sequence'],
                'payments_operation_sequence_unique',
            );
        });

        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->ulid('operation_request_id')->nullable()->unique();
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->ulid('operation_request_id')->nullable()->index();
            $table->unsignedSmallInteger('operation_sequence')->nullable();
            $table->unique(
                ['operation_request_id', 'operation_sequence'],
                'stock_movements_operation_sequence_unique',
            );
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->ulid('operation_request_id')->nullable()->index();
            $table->unsignedSmallInteger('operation_sequence')->nullable();
            $table->unique(
                ['operation_request_id', 'operation_sequence'],
                'journal_entries_operation_sequence_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropUnique('journal_entries_operation_sequence_unique');
            $table->dropColumn(['operation_request_id', 'operation_sequence']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropUnique('stock_movements_operation_sequence_unique');
            $table->dropColumn(['operation_request_id', 'operation_sequence']);
        });

        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->dropUnique(['operation_request_id']);
            $table->dropColumn('operation_request_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_operation_sequence_unique');
            $table->dropColumn(['operation_request_id', 'operation_sequence']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['operation_request_id']);
            $table->dropColumn('operation_request_id');
        });

        Schema::dropIfExists('outbox_events');
        Schema::dropIfExists('operation_requests');
    }
};
