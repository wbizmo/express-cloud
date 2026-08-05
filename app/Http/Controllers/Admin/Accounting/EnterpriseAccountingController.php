<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Branch;
use App\Models\JournalLine;
use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\EnterpriseFinancialStatements;
use App\Services\Accounting\PeriodCloseService;
use App\Services\Accounting\TreasuryService;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\BranchAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class EnterpriseAccountingController
{
    public function __construct(
        private EnterpriseFinancialStatements $statements,
        private PeriodCloseService $periods,
        private BankReconciliationService $bankReconciliation,
        private TreasuryService $treasury,
        private MoneyInput $money,
        private BranchAccess $branches,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $asOf = CarbonImmutable::parse(
            $request->string('as_of', today()->toDateString())->toString(),
        );
        $from = CarbonImmutable::parse(
            $request->string('from', $asOf->startOfMonth()->toDateString())->toString(),
        );
        $branchId = $request->string('branch_id')->toString() ?: null;

        if (! $actor->is_allowed_all_branches && $branchId === null) {
            $branchId = $this->branches->allowedBranchIds($actor)[0] ?? null;
            abort_unless($branchId !== null, 404);
        }
        if ($branchId !== null) {
            $this->branches->enforce($actor, $branchId);
        }

        $bankAccounts = $this->accessibleBankAccounts($actor);
        $treasuryAccounts = $this->accessibleTreasuryAccounts($actor);

        return view('admin.accounting.enterprise', [
            'asOf' => $asOf,
            'from' => $from,
            'branchId' => $branchId,
            'branches' => $this->branches->scope($actor, Branch::query(), 'id')
                ->orderBy('name')->get(['id', 'name']),
            'trialBalance' => $this->statements->trialBalance($asOf, $branchId),
            'profitAndLoss' => $this->statements->profitAndLoss($from, $asOf, $branchId),
            'balanceSheet' => $this->statements->balanceSheet($asOf, $branchId),
            'cashFlow' => $this->statements->cashFlow($from, $asOf, $branchId),
            'cashBook' => $this->statements->cashBook($from, $asOf, $branchId),
            'bankBook' => $this->statements->bankBook($from, $asOf, $branchId),
            'taxLedger' => $this->statements->taxLedger($from, $asOf, $branchId),
            'inventoryValuation' => $this->statements->inventoryValuation($branchId),
            'control' => $this->statements->controlReconciliation($asOf, $branchId),
            'agedReceivables' => $this->statements->agedReceivables($asOf, $branchId),
            'agedPayables' => $this->statements->agedPayables($asOf, $branchId),
            'periods' => AccountingPeriod::query()->latest('ends_on')->limit(24)->get(),
            'bankAccounts' => (clone $bankAccounts)->orderBy('name')->get(),
            'bankStatements' => BankStatementImport::query()
                ->whereIn('bank_account_id', (clone $bankAccounts)->select('id'))
                ->with('bankAccount')->latest('imported_at')->limit(20)->get(),
            'treasuryAccounts' => (clone $treasuryAccounts)
                ->where('is_active', true)->orderBy('name')->get(),
            'treasuryMovements' => TreasuryMovement::query()
                ->when(
                    ! $actor->is_allowed_all_branches,
                    fn (Builder $query): Builder => $query->whereIn(
                        'branch_id',
                        $this->branches->allowedBranchIds($actor),
                    ),
                )
                ->latest('occurred_at')->limit(20)->get(),
        ]);
    }

    public function close(Request $request, AccountingPeriod $period): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        abort_unless($actor->is_allowed_all_branches, 404);

        $this->periods->close(
            $period,
            $actor,
            $request->string('notes')->trim()->toString() ?: null,
        );

        return back()->with('status', 'Accounting period reconciled, closed and locked.');
    }

    public function treasuryTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'source_treasury_account_id' => ['required', 'ulid', 'different:destination_treasury_account_id'],
            'destination_treasury_account_id' => ['required', 'ulid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['required', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:160'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        /** @var TreasuryAccount $source */
        $source = $this->accessibleTreasuryAccounts($actor)
            ->whereKey($validated['source_treasury_account_id'])->firstOrFail();
        /** @var TreasuryAccount $destination */
        $destination = $this->accessibleTreasuryAccounts($actor)
            ->whereKey($validated['destination_treasury_account_id'])->firstOrFail();
        $amountKobo = $this->money->toKobo($validated['amount']) ?? 0;

        $this->treasury->transfer(
            $source,
            $destination,
            $actor,
            $amountKobo,
            $validated['idempotency_key'],
            $validated['memo'],
            $validated['reference'] ?? null,
        );

        return back()->with('status', 'Treasury transfer posted and linked to a balanced journal.');
    }

    public function importBankStatement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'bank_account_id' => ['required', 'ulid'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'lines_json' => ['required', 'json'],
        ]);
        $lines = json_decode($validated['lines_json'], true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($lines)) {
            throw new \InvalidArgumentException('Statement lines must decode to an array.');
        }
        /** @var Account $actor */
        $actor = $request->user();
        /** @var BankAccount $bank */
        $bank = $this->accessibleBankAccounts($actor)
            ->whereKey($validated['bank_account_id'])->firstOrFail();

        $this->bankReconciliation->import(
            $bank,
            $actor,
            $validated['idempotency_key'],
            $validated['starts_on'],
            $validated['ends_on'],
            $this->money->toKobo($validated['opening_balance']) ?? 0,
            $this->money->toKobo($validated['closing_balance']) ?? 0,
            array_values($lines),
        );

        return back()->with('status', 'Bank statement imported. Match every line before finalization.');
    }

    public function matchBankLine(Request $request, BankStatementLine $statementLine): RedirectResponse
    {
        $validated = $request->validate([
            'journal_line_id' => ['required', 'ulid'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        /** @var BankStatementImport $statement */
        $statement = $statementLine->statement()->firstOrFail();
        /** @var BankAccount $bankAccount */
        $bankAccount = $statement->bankAccount()->firstOrFail();
        $this->enforceBranchOwnedRecord($actor, $bankAccount->branch_id);

        /** @var JournalLine $journalLine */
        $journalLine = JournalLine::query()->findOrFail($validated['journal_line_id']);
        if (is_string($journalLine->branch_id) && $journalLine->branch_id !== '') {
            $this->branches->enforce($actor, $journalLine->branch_id);
        }
        $amountKobo = isset($validated['amount'])
            ? $this->money->toKobo($validated['amount'])
            : null;

        $this->bankReconciliation->match(
            $statementLine,
            $journalLine,
            $actor,
            $amountKobo,
        );

        return back()->with('status', 'Bank statement line matched.');
    }

    public function finalizeBankStatement(
        Request $request,
        BankStatementImport $statement,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        /** @var BankAccount $bankAccount */
        $bankAccount = $statement->bankAccount()->firstOrFail();
        $this->enforceBranchOwnedRecord($actor, $bankAccount->branch_id);
        $this->bankReconciliation->finalize($statement);

        return back()->with('status', 'Bank statement fully reconciled.');
    }

    /** @return Builder<BankAccount> */
    private function accessibleBankAccounts(Account $actor): Builder
    {
        return BankAccount::query()->when(
            ! $actor->is_allowed_all_branches,
            fn (Builder $query): Builder => $query->whereIn(
                'branch_id',
                $this->branches->allowedBranchIds($actor),
            ),
        );
    }

    /** @return Builder<TreasuryAccount> */
    private function accessibleTreasuryAccounts(Account $actor): Builder
    {
        return TreasuryAccount::query()->when(
            ! $actor->is_allowed_all_branches,
            fn (Builder $query): Builder => $query->whereIn(
                'branch_id',
                $this->branches->allowedBranchIds($actor),
            ),
        );
    }

    private function enforceBranchOwnedRecord(Account $actor, mixed $branchId): void
    {
        if (is_string($branchId) && $branchId !== '') {
            $this->branches->enforce($actor, $branchId);

            return;
        }

        abort_unless($actor->is_allowed_all_branches, 404);
    }
}
