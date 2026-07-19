<?php

declare(strict_types=1);

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

final class OpeningBalanceController
{
    public function __construct(private AuditLogger $audit) {}

    public function create(): View
    {
        $accounts = LedgerAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $periods = AccountingPeriod::query()
            ->where('status', 'open')
            ->orderByDesc('starts_on')
            ->get(['id', 'name']);

        return view('admin.accounting.opening-balance.create', [
            'accounts' => $accounts,
            'periods' => $periods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entry_date' => ['required', 'date'],
            'accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'memo' => ['required', 'string', 'max:500'],
            'balances' => ['required', 'array'],
            'balances.*.ledger_account_id' => ['required', 'exists:ledger_accounts,id'],
            'balances.*.debit_kobo' => ['nullable', 'integer', 'min:0'],
            'balances.*.credit_kobo' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            $entry = JournalEntry::query()->create([
                'journal_number' => $this->generateOpeningJournalNumber(),
                'entry_date' => $request->input('entry_date'),
                'accounting_period_id' => $request->input('accounting_period_id'),
                'status' => 'posted',
                'memo' => $request->input('memo', 'Opening balance entry'),
                'created_by_account_id' => $request->user()?->id,
                'posted_at' => now(),
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($request->input('balances') as $balance) {
                $debit = (int) ($balance['debit_kobo'] ?? 0);
                $credit = (int) ($balance['credit_kobo'] ?? 0);
                $totalDebit += $debit;
                $totalCredit += $credit;

                JournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'ledger_account_id' => $balance['ledger_account_id'],
                    'debit_kobo' => $debit,
                    'credit_kobo' => $credit,
                    'description' => 'Opening balance',
                ]);
            }

            if ($totalDebit !== $totalCredit) {
                throw new \RuntimeException('Total debits must equal total credits.');
            }

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

    private function generateOpeningJournalNumber(): string
    {
        $last = JournalEntry::query()
            ->where('journal_number', 'LIKE', 'OP-%')
            ->orderByDesc('journal_number')
            ->first();

        $number = $last ? (int) Str::after($last->journal_number, 'OP-') + 1 : 1;

        return 'OP-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
