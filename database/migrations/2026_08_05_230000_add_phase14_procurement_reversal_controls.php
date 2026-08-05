<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->foreignUlid('voided_by_account_id')->nullable()->after('received_by_account_id')
                ->constrained('accounts')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('received_at');
            $table->text('void_reason')->nullable()->after('notes');
            $table->index(['status', 'voided_at']);
        });

        Schema::table('landed_cost_allocations', function (Blueprint $table): void {
            $table->string('status', 24)->default('active')->after('allocation_method')->index();
            $table->foreignUlid('reversed_by_account_id')->nullable()->after('created_by_account_id')
                ->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('reversal_journal_entry_id')->nullable()->after('journal_entry_id')
                ->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('allocated_at');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });
    }

    public function down(): void
    {
        Schema::table('landed_cost_allocations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reversed_by_account_id');
            $table->dropConstrainedForeignId('reversal_journal_entry_id');
            $table->dropColumn(['status', 'reversed_at', 'reversal_reason']);
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'voided_at']);
            $table->dropConstrainedForeignId('voided_by_account_id');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
