<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->foreignUlid('ledger_account_id')
                ->nullable()
                ->after('is_active')
                ->constrained('ledger_accounts')
                ->nullOnDelete();
        });

        // Backfill the pre-existing system default payment methods so
        // postings switch to the explicit link immediately, with no gap
        // in coverage for methods created before this migration.
        $accountByCode = static function (string $code): ?string {
            $row = DB::table('ledger_accounts')
                ->where('code', $code)
                ->first(['id']);

            return $row?->id;
        };

        $cashAccountId = $accountByCode('1000');
        $bankAccountId = $accountByCode('1010');
        $cardClearingAccountId = $accountByCode('1020');

        if ($cashAccountId !== null) {
            DB::table('payment_methods')
                ->where('is_system_default', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%cash%'])
                ->whereNull('ledger_account_id')
                ->update(['ledger_account_id' => $cashAccountId]);
        }

        if ($bankAccountId !== null) {
            DB::table('payment_methods')
                ->where('is_system_default', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%bank%'])
                ->whereNull('ledger_account_id')
                ->update(['ledger_account_id' => $bankAccountId]);
        }

        if ($cardClearingAccountId !== null) {
            DB::table('payment_methods')
                ->where('is_system_default', true)
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%card%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%pos%']);
                })
                ->whereNull('ledger_account_id')
                ->update(['ledger_account_id' => $cardClearingAccountId]);
        }
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ledger_account_id');
        });
    }
};
