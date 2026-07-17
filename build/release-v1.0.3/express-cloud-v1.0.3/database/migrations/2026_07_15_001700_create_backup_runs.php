<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('backup_type', 30);
            $table->string('status', 30)->index();
            $table->string('disk', 60);
            $table->string('path', 500)->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->json('manifest')->nullable();
            $table->text('failure_message')->nullable();
            $table->foreignUlid('requested_by_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
