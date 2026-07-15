<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->string('session_identifier', 255)->unique();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index([
                'account_id',
                'revoked_at',
                'last_activity_at',
            ], 'account_sessions_activity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_sessions');
    }
};
