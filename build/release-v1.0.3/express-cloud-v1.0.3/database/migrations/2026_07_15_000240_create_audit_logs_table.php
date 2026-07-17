<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('actor_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('actor_name', 220)->nullable();
            $table->string('actor_role_snapshot', 220)->nullable();
            $table->string('action', 180)->index();
            $table->string('entity_type', 100)->index();
            $table->string('entity_id', 64)->nullable()->index();
            $table->foreignUlid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('context')->nullable();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['entity_type', 'entity_id', 'occurred_at'],
                'audit_logs_entity_time_index',
            );
            $table->index(
                ['branch_id', 'occurred_at'],
                'audit_logs_branch_time_index',
            );
            $table->index(
                ['actor_account_id', 'occurred_at'],
                'audit_logs_actor_time_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
