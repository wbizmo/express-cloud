<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\OperationRequest;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class BatchJournalEntryController
{
    public function __construct(
        private AuditLogger $audit,
        private CommandBoundary $commands,
        private FinancialPostingCoordinator $postings,
    ) {}

    public function create(): View
    {
        return view('admin.accounting.batch-journal.create', [
            'periods' => AccountingPeriod::query()
                ->where('status', 'open')
                ->orderByDesc('starts_on')
                ->get(['id', 'name']),
            'accounts' => LedgerAccount::query()
                ->where('is_active', true)
                ->where('allow_manual_posting', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.accounting_period_id' => [
                'required',
                'exists:accounting_periods,id',
            ],
            'entries.*.status' => ['required', 'in:draft,posted'],
            'entries.*.memo' => ['required', 'string', 'max:500'],
            'entries.*.lines' => ['required', 'array', 'min:2'],
            'entries.*.lines.*.ledger_account_id' => [
                'required',
                'exists:ledger_accounts,id',
            ],
            'entries.*.lines.*.debit_kobo' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'entries.*.lines.*.credit_kobo' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'entries.*.lines.*.description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);
        $entries = $validated['entries'];
        $this->assertBalanced($entries);
        /** @var Account $actor */
        $actor = $request->user();
        $result = $this->commands->execute(
            'journal.batch',
            (string) $validated['idempotency_key'],
            $validated,
            $actor,
            null,
            function (OperationRequest $operation) use (
                $entries,
                $actor,
                $request,
            ): JournalEntry {
                $first = null;

                foreach ($entries as $index => $entryData) {
                    $entry = JournalEntry::query()->create([
                        'journal_number' => $this->journalNumber(),
                        'entry_date' => $entryData['entry_date'],
                        'accounting_period_id' => $entryData['accounting_period_id'],
                        'status' => $entryData['status'],
                        'memo' => $entryData['memo'],
                        'created_by_account_id' => $actor->getKey(),
                        'operation_request_id' => $operation->getKey(),
                        'operation_sequence' => $index + 1,
                        'posted_at' => $entryData['status'] === 'posted'
                            ? now()
                            : null,
                    ]);

                    foreach ($entryData['lines'] as $lineData) {
                        JournalLine::query()->create([
                            'journal_entry_id' => $entry->getKey(),
                            'ledger_account_id' => $lineData['ledger_account_id'],
                            'debit_kobo' => (int) ($lineData['debit_kobo'] ?? 0),
                            'credit_kobo' => (int) ($lineData['credit_kobo'] ?? 0),
                            'description' => $lineData['description'] ?? null,
                        ]);
                    }

                    if ($entryData['status'] === 'posted') {
                        $this->postings->registerExistingJournal(
                            $entry,
                            'batch-posted',
                        );
                    } else {
                        $this->postings->nonPosting(
                            $entry,
                            'batch-draft',
                            'draft-journal',
                            $operation,
                        );
                    }

                    $this->audit->record(
                        $request,
                        'journal_entry.batch_created',
                        'journal_entry',
                        $entry,
                        after: [
                            'journal_number' => $entry->journal_number,
                            'operation_id' => (string) $operation->getKey(),
                        ],
                    );
                    $first ??= $entry;
                }

                if (! $first instanceof JournalEntry) {
                    throw new \LogicException(
                        'The journal batch did not create an entry.',
                    );
                }

                return $first;
            },
        );

        if (! $result instanceof JournalEntry) {
            throw new \LogicException(
                'The journal command returned an invalid result.',
            );
        }

        return redirect()
            ->route('admin.accounting.journal-entries.index')
            ->with('status', 'Batch of journal entries created successfully.');
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function assertBalanced(array $entries): void
    {
        $errors = [];

        foreach ($entries as $index => $entryData) {
            $debit = 0;
            $credit = 0;

            foreach ($entryData['lines'] as $line) {
                $debit += (int) ($line['debit_kobo'] ?? 0);
                $credit += (int) ($line['credit_kobo'] ?? 0);
            }

            if ($debit !== $credit || $debit <= 0) {
                $errors[] = sprintf(
                    'Entry #%d is unbalanced or empty (Debit: %d, Credit: %d).',
                    $index + 1,
                    $debit,
                    $credit,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'entries' => implode(' ', $errors),
            ]);
        }
    }

    private function journalNumber(): string
    {
        return 'JE-'.now()->format('ymd').'-'
            .Str::upper(substr((string) Str::ulid(), -10));
    }
}
