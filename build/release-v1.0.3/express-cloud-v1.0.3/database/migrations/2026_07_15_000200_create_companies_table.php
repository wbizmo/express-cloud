<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('legal_name', 180);
            $table->string('trading_name', 180)->nullable();
            $table->text('head_office_address');
            $table->string('phone', 40)->nullable();
            $table->text('email_encrypted')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->boolean('is_configured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
