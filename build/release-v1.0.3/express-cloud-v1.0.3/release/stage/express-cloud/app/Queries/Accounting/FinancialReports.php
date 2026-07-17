<?php

declare(strict_types=1);

namespace App\Queries\Accounting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinancialReports
{
    /** @return Collection<int, \stdClass> */
    public function trialBalance(string $from, string $to): Collection
    {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->join(
                'ledger_accounts',
                'ledger_accounts.id',
                '=',
                'journal_lines.ledger_account_id',
            )
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->groupBy(
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            )
            ->orderBy('ledger_accounts.code')
            ->select([
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            ])
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo')
            ->get();
    }

    /** @return Collection<int, \stdClass> */
    public function generalLedger(
        string $accountId,
        string $from,
        string $to,
    ): Collection {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->where('journal_lines.ledger_account_id', $accountId)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.journal_number')
            ->get([
                'journal_entries.journal_number',
                'journal_entries.entry_date',
                'journal_entries.memo',
                'journal_lines.debit_kobo',
                'journal_lines.credit_kobo',
                'journal_lines.description',
            ]);
    }
}
