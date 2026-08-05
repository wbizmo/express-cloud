<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class JournalPoster
{
    public function __construct(private PeriodResolver $periods) {}

    /**
     * @param list<array{
     *   account_id:string,
     *   debit_kobo?:int,
     *   credit_kobo?:int,
     *   branch_id?:string|null,
     *   customer_id?:string|null,
     *   supplier_id?:string|null,
     *   warehouse_id?:string|null,
     *   tax_rate_id?:string|null,
     *   tax_basis_kobo?:int,
     *   tax_amount_kobo?:int,
     *   due_on?:string|null,
     *   subledger_reference?:string|null,
     *   description?:string|null
     * }> $lines
     */
    public function post(
        CarbonInterface $date,
        string $memo,
        array $lines,
        ?string $branchId = null,
        ?string $actorId = null,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $sourceEvent = null,
        ?string $operationRequestId = null,
        ?int $operationSequence = null,
        string $bookType = 'general',
    ): JournalEntry {
        $lines = array_values(array_filter(
            $lines,
            static fn (array $line): bool => (
                (int) ($line['debit_kobo'] ?? 0) > 0
                || (int) ($line['credit_kobo'] ?? 0) > 0
            ),
        ));

        $debits = array_sum(
            array_map(
                static fn (array $line): int => (int) ($line['debit_kobo'] ?? 0),
                $lines,
            ),
        );
        $credits = array_sum(
            array_map(
                static fn (array $line): int => (int) ($line['credit_kobo'] ?? 0),
                $lines,
            ),
        );

        if ($debits <= 0 || $debits !== $credits) {
            throw new \DomainException(
                'Journal debits and credits must be equal and greater than zero.',
            );
        }

        return DB::transaction(function () use (
            $date,
            $memo,
            $lines,
            $branchId,
            $actorId,
            $sourceType,
            $sourceId,
            $sourceEvent,
            $operationRequestId,
            $operationSequence,
            $bookType,
        ): JournalEntry {
            if (
                $sourceType !== null
                && $sourceId !== null
                && $sourceEvent !== null
            ) {
                /** @var JournalEntry|null $existing */
                $existing = JournalEntry::query()
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where('source_event', $sourceEvent)
                    ->first();

                if ($existing !== null) {
                    return $existing->load('lines');
                }
            }

            $period = $this->periods->forDate($date);

            $entry = JournalEntry::query()->create([
                'journal_number' => 'JRN-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(8)),
                'entry_date' => $date->toDateString(),
                'accounting_period_id' => $period->getKey(),
                'branch_id' => $branchId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_event' => $sourceEvent,
                'operation_request_id' => $operationRequestId,
                'operation_sequence' => $operationSequence,
                'status' => 'posted',
                'book_type' => $bookType,
                'memo' => $memo,
                'created_by_account_id' => $actorId,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $debit = (int) ($line['debit_kobo'] ?? 0);
                $credit = (int) ($line['credit_kobo'] ?? 0);

                if (($debit > 0) === ($credit > 0)) {
                    throw new \DomainException(
                        'Each journal line must contain either a debit or a credit.',
                    );
                }

                JournalLine::query()->create([
                    'journal_entry_id' => $entry->getKey(),
                    'ledger_account_id' => $line['account_id'],
                    'branch_id' => $line['branch_id'] ?? $branchId,
                    'customer_id' => $line['customer_id'] ?? null,
                    'supplier_id' => $line['supplier_id'] ?? null,
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'tax_rate_id' => $line['tax_rate_id'] ?? null,
                    'tax_basis_kobo' => (int) ($line['tax_basis_kobo'] ?? 0),
                    'tax_amount_kobo' => (int) ($line['tax_amount_kobo'] ?? 0),
                    'due_on' => $line['due_on'] ?? null,
                    'subledger_reference' => $line['subledger_reference'] ?? null,
                    'debit_kobo' => $debit,
                    'credit_kobo' => $credit,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines');
        }, 3);
    }
}
