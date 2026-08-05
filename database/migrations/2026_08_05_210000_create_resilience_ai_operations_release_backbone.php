<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_snapshots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('cache_key', 64)->unique();
            $table->string('company_key', 80)->default('default');
            $table->char('branch_scope_hash', 64);
            $table->char('permission_scope_hash', 64);
            $table->string('period_key', 80);
            $table->string('metric_version', 40);
            $table->json('payload');
            $table->char('evidence_hash', 64);
            $table->timestamp('generated_at');
            $table->timestamp('stale_at');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['expires_at', 'metric_version'], 'business_snapshots_expiry_idx');
        });

        Schema::create('business_snapshot_evidence', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_snapshot_id')->constrained('business_snapshots')->cascadeOnDelete();
            $table->string('metric_key', 120);
            $table->string('source_table', 120);
            $table->char('source_query_hash', 64);
            $table->json('value_payload');
            $table->timestamp('observed_at');
            $table->timestamps();
            $table->unique(['business_snapshot_id', 'metric_key'], 'snapshot_evidence_metric_uq');
        });

        Schema::create('daily_close_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('business_date')->unique();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('lock_token', 120)->nullable();
            $table->json('summary')->nullable();
            $table->string('failure_step', 120)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('daily_close_run_id')->constrained('daily_close_runs')->cascadeOnDelete();
            $table->string('recipient', 320);
            $table->string('notification_type', 80);
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('provider_message_id', 255)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['daily_close_run_id', 'recipient', 'notification_type'], 'notification_delivery_uq');
        });

        Schema::create('external_cron_nonces', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('nonce', 120)->unique();
            $table->unsignedBigInteger('timestamp');
            $table->char('signature_hash', 64);
            $table->timestamp('used_at');
            $table->timestamp('expires_at');
            $table->index('expires_at');
        });

        Schema::create('release_verification_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('release_name', 120);
            $table->char('commit_sha', 40)->nullable();
            $table->string('status', 32);
            $table->json('checks')->nullable();
            $table->string('artifact_path', 500)->nullable();
            $table->char('artifact_sha256', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });

        Schema::create('data_backfill_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_label', 160);
            $table->string('status', 32);
            $table->json('checkpoint')->nullable();
            $table->json('counts')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });

        Schema::table('lisa_conversations', function (Blueprint $table): void {
            $table->index(['account_id', 'last_message_at'], 'lisa_conversations_account_last_idx');
        });
        Schema::table('lisa_messages', function (Blueprint $table): void {
            $table->index(['conversation_id', 'created_at'], 'lisa_messages_conversation_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lisa_messages', function (Blueprint $table): void {
            $table->dropIndex('lisa_messages_conversation_created_idx');
        });
        Schema::table('lisa_conversations', function (Blueprint $table): void {
            $table->dropIndex('lisa_conversations_account_last_idx');
        });
        Schema::dropIfExists('data_backfill_runs');
        Schema::dropIfExists('release_verification_runs');
        Schema::dropIfExists('external_cron_nonces');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('daily_close_runs');
        Schema::dropIfExists('business_snapshot_evidence');
        Schema::dropIfExists('business_snapshots');
    }
};
