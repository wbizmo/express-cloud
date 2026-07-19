<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BatchJournalEntryController
{
    public function __construct(private AuditLogger $audit) {}

    public function create(): View
    {
        $periods = AccountingPeriod::query()
            ->where('status', 'open')
            ->orderByDesc('starts_on')
            ->get(['id', 'name']);

        $accounts = LedgerAccount::query()
            ->where('is_active', true)
            ->where('allow_manual_posting', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('admin.accounting.batch-journal.create', [
            'periods' => $periods,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'entries.*.status' => ['required', 'in:draft,posted'],
            'entries.*.memo' => ['required', 'string', 'max:500'],
            'entries.*.lines' => ['required', 'array', 'min:2'],
            'entries.*.lines.*.ledger_account_id' => ['required', 'exists:ledger_accounts,id'],
            'entries.*.lines.*.debit_kobo' => ['nullable', 'integer', 'min:0'],
            'entries.*.lines.*.credit_kobo' => ['nullable', 'integer', 'min:0'],
            'entries.*.lines.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        // Pre‑validation: check each entry's balance
        $errors = [];
        foreach ($request->input('entries') as $index => $entryData) {
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($entryData['lines'] as $line) {
                $totalDebit += (int) ($line['debit_kobo'] ?? 0);
                $totalCredit += (int) ($line['credit_kobo'] ?? 0);
            }
            if ($totalDebit !== $totalCredit) {
                $errors[] = "Entry #" . ($index + 1) . " is unbalanced (Debit: $totalDebit, Credit: $totalCredit).";
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(['entries' => implode(' ', $errors)])->withInput();
        }

        // All balanced – proceed
        DB::transaction(function () use ($request) {
            foreach ($request->input('entries') as $entryData) {
                $entry = JournalEntry::query()->create([
                    'journal_number' => $this->generateJournalNumber(),
                    'entry_date' => $entryData['entry_date'],
                    'accounting_period_id' => $entryData['accounting_period_id'],
                    'status' => $entryData['status'],
                    'memo' => $entryData['memo'],
                    'created_by_account_id' => $request->user()?->id,
                    'posted_at' => $entryData['status'] === 'posted' ? now() : null,
                ]);

                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($entryData['lines'] as $lineData) {
                    $debit = (int) ($lineData['debit_kobo'] ?? 0);
                    $credit = (int) ($lineData['credit_kobo'] ?? 0);
                    $totalDebit += $debit;
                    $totalCredit += $credit;

                    JournalLine::query()->create([
                        'journal_entry_id' => $entry->id,
                        'ledger_account_id' => $lineData['ledger_account_id'],
                        'debit_kobo' => $debit,
                        'credit_kobo' => $credit,
                        'description' => $lineData['description'] ?? null,
                    ]);
                }

                // Safety check (should never trigger)
                if ($totalDebit !== $totalCredit) {
                    throw new \RuntimeException("Journal entry {$entry->journal_number} is unbalanced.");
                }

                $this->audit->record(
                    $request,
                    'journal_entry.batch_created',
                    'journal_entry',
                    $entry,
                    after: ['journal_number' => $entry->journal_number],
                );
            }
        });

        return redirect()
            ->route('admin.accounting.journal-entries.index')
            ->with('status', 'Batch of journal entries created successfully.');
    }

    private function generateJournalNumber(): string
    {
        $last = JournalEntry::query()
            ->where('journal_number', 'LIKE', 'JE-%')
            ->orderByDesc('journal_number')
            ->first();

        $number = $last ? (int) Str::after($last->journal_number, 'JE-') + 1 : 1;

        return 'JE-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
