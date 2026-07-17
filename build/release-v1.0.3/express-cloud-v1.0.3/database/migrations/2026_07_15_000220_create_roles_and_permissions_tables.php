<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('group', 80)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignUlid('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            $table->foreignUlid('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('account_role', function (Blueprint $table): void {
            $table->foreignUlid('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignUlid('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['account_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_role');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
