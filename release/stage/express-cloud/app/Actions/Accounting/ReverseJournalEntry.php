<?php

declare(strict_types=1);

namespace App\Actions\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPoster;
use Illuminate\Support\Facades\DB;

final readonly class ReverseJournalEntry
{
    public function __construct(private JournalPoster $journals) {}

    public function execute(
        JournalEntry $entry,
        Account $actor,
        string $memo,
    ): JournalEntry {
        if ((string) $entry->status !== 'posted') {
            throw new \DomainException(
                'Only posted journals can be reversed.',
            );
        }

        return DB::transaction(function () use (
            $entry,
            $actor,
            $memo,
        ): JournalEntry {
            $reversal = $this->journals->post(
                now(),
                $memo,
                $entry->lines->map(
                    static fn ($line): array => [
                        'account_id' => $line->ledger_account_id,
                        'debit_kobo' => $line->credit_kobo,
                        'credit_kobo' => $line->debit_kobo,
                        'branch_id' => $line->branch_id,
                        'customer_id' => $line->customer_id,
                        'supplier_id' => $line->supplier_id,
                        'description' => 'Reversal of '
                            .$entry->journal_number,
                    ],
                )->all(),
                $entry->branch_id,
                (string) $actor->getKey(),
                JournalEntry::class,
                (string) $entry->getKey(),
                'reversal',
            );

            $reversal->forceFill([
                'reversal_of_entry_id' => $entry->getKey(),
            ])->save();

            $entry->forceFill([
                'status' => 'reversed',
                'reversed_at' => now(),
            ])->save();

            return $reversal;
        }, 3);
    }
}
