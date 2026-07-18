<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lisa_conversations')) {
            Schema::create('lisa_conversations', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('title', 180)->default('New conversation');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('lisa_messages')) {
            Schema::create('lisa_messages', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('conversation_id')->constrained('lisa_conversations')->cascadeOnDelete();
                $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->enum('role', ['user', 'assistant', 'system']);
                $table->longText('content');
                $table->json('context_snapshot')->nullable();
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('daily_report_runs')) {
            Schema::create('daily_report_runs', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->date('report_date')->unique();
                $table->string('status', 30)->default('pending');
                $table->json('generated_files')->nullable();
                $table->longText('summary_html')->nullable();
                $table->text('failure_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
        foreach (['products', 'customers', 'suppliers', 'branches', 'product_categories', 'brands', 'roles', 'accounts'] as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) use ($name): void {
                if (! Schema::hasColumn($name, 'deprecated_at')) {
                    $table->timestamp('deprecated_at')->nullable()->index();
                }
                if (! Schema::hasColumn($name, 'deprecated_by_account_id')) {
                    $table->foreignUlid('deprecated_by_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn($name, 'deprecation_reason')) {
                    $table->text('deprecation_reason')->nullable();
                }
            });
        }
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (! Schema::hasColumn('customers', 'whatsapp_phone')) {
                    $table->string('whatsapp_phone', 40)->nullable();
                }
                if (! Schema::hasColumn('customers', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_runs');
        Schema::dropIfExists('lisa_messages');
        Schema::dropIfExists('lisa_conversations');
    }
};
