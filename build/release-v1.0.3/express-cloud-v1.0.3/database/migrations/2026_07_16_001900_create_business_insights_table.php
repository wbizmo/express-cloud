<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_insights', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('category', 40)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title', 160);
            $table->text('summary');
            $table->text('recommendation')->nullable();
            $table->json('evidence')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamp('generated_at')->index();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['category', 'branch_id', 'period_start', 'period_end', 'title'], 'business_insights_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_insights');
    }
};
