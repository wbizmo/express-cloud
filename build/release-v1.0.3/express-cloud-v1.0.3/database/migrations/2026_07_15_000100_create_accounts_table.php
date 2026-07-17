<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->text('email_encrypted')->nullable();
            $table->text('login_key_encrypted');
            $table->char('login_key_blind_index', 64)->unique();
            $table->unsignedSmallInteger('login_key_version')->default(1);
            $table->string('profile_picture_path', 500)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('last_authenticated_at')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index(['status', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
