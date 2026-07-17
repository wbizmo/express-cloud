<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('event_type', 64)->index();
            $table->foreignUlid('actor_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->foreignUlid('subject_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->string('session_identifier', 255)->nullable()->index();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index([
                'event_type',
                'occurred_at',
            ], 'security_events_type_time_index');

            $table->index([
                'subject_account_id',
                'occurred_at',
            ], 'security_events_subject_time_index');

            $table->index([
                'actor_account_id',
                'occurred_at',
            ], 'security_events_actor_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
