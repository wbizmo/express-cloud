<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class OpeningBalanceController
{
    public function __construct(
        private AuditLogger $audit,
        private FinancialPostingCoordinator $postings,
    ) {}

    public function create(): View
    {
        return view('admin.accounting.opening-balance.create', [
            'accounts' => LedgerAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type']),
            'periods' => AccountingPeriod::query()
                ->where('status', 'open')
                ->orderByDesc('starts_on')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'memo' => ['required', 'string', 'max:500'],
            'balances' => ['required', 'array', 'min:2'],
            'balances.*.ledger_account_id' => ['required', 'exists:ledger_accounts,id'],
            'balances.*.debit_kobo' => ['nullable', 'integer', 'min:0'],
            'balances.*.credit_kobo' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            /** @var AccountingPeriod $period */
            $period = AccountingPeriod::query()
                ->whereKey($validated['accounting_period_id'])
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($period->status === 'open', 422, 'The accounting period is not open.');

            $entry = JournalEntry::query()->create([
                'journal_number' => 'OP-'.Str::upper(substr((string) Str::ulid(), -12)),
                'entry_date' => $validated['entry_date'],
                'accounting_period_id' => $period->getKey(),
                'status' => 'posted',
                'memo' => $validated['memo'],
                'created_by_account_id' => $request->user()?->id,
                'posted_at' => now(),
            ]);

            $debits = 0;
            $credits = 0;
            foreach ($validated['balances'] as $balance) {
                $debit = (int) ($balance['debit_kobo'] ?? 0);
                $credit = (int) ($balance['credit_kobo'] ?? 0);
                if (($debit > 0) === ($credit > 0)) {
                    throw new \DomainException(
                        'Each opening balance line requires exactly one debit or credit.',
                    );
                }
                $debits += $debit;
                $credits += $credit;
                JournalLine::query()->create([
                    'journal_entry_id' => $entry->getKey(),
                    'ledger_account_id' => $balance['ledger_account_id'],
                    'debit_kobo' => $debit,
                    'credit_kobo' => $credit,
                    'description' => 'Opening balance',
                ]);
            }

            if ($debits <= 0 || $debits !== $credits) {
                throw new \DomainException('Opening balance debits and credits must balance.');
            }

            $this->postings->registerExistingJournal(
                $entry,
                'opening-balance',
            );

            $this->audit->record(
                $request,
                'opening_balance.posted',
                'journal_entry',
                $entry,
                after: ['journal_number' => $entry->journal_number],
            );
        });

        return redirect()
            ->route('admin.accounting.journal-entries.index')
            ->with('status', 'Opening balance posted successfully.');
    }
}
