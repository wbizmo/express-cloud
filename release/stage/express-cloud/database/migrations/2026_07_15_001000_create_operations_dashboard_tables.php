<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_recipients', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email', 255)->unique();
            $table->string('label', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('added_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['is_active', 'email']);
        });

        Schema::create('business_settings', function (Blueprint $table): void {
            $table->string('singleton_key', 20)->primary();
            $table->string('business_name', 150);
            $table->string('business_logo_path', 500)->nullable();
            $table->text('head_office_address');
            $table->time('end_of_day_digest_time')->default('21:00:00');
            $table->unsignedSmallInteger(
                'session_inactivity_minutes',
            )->default(20);
            $table->timestamps();
        });

        Schema::create('end_of_day_digests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('business_date')->unique();
            $table->string('status', 30)->index();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('notification_type', 40)->index();
            $table->string('title', 180);
            $table->text('message');
            $table->string('entity_type', 80)->nullable()->index();
            $table->string('entity_id', 64)->nullable()->index();
            $table->foreignUlid('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['notification_type', 'resolved_at', 'occurred_at'],
                'admin_notifications_open_index',
            );
            $table->unique(
                [
                    'notification_type',
                    'entity_type',
                    'entity_id',
                    'branch_id',
                    'resolved_at',
                ],
                'admin_notifications_open_entity_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('end_of_day_digests');
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('alert_recipients');
    }
};
