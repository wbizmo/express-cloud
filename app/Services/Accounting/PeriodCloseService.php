<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingCloseBatch;
use App\Models\AccountingPeriod;
use App\Models\BankStatementImport;
use App\Models\JournalEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class PeriodCloseService
{
    public function __construct(
        private FinancialPostingReconciler $postings,
        private EnterpriseFinancialStatements $statements,
    ) {}

    public function close(
        AccountingPeriod $period,
        Account $actor,
        ?string $notes = null,
    ): AccountingCloseBatch {
        return DB::transaction(function () use ($period, $actor, $notes): AccountingCloseBatch {
            /** @var AccountingPeriod $locked */
            $locked = AccountingPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== PeriodStatus::Open) {
                throw new \DomainException('Only an open accounting period can be closed.');
            }

            $postingAudit = $this->postings->audit();
            $startsOn = CarbonImmutable::parse((string) $locked->starts_on);
            $endsOn = CarbonImmutable::parse((string) $locked->ends_on);
            $control = $this->statements->controlReconciliation($endsOn);
            $balanceSheet = $this->statements->balanceSheet($endsOn);
            $unreconciledStatements = BankStatementImport::query()
                ->whereDate('starts_on', '<=', $locked->ends_on)
                ->whereDate('ends_on', '>=', $locked->starts_on)
                ->where('status', '!=', 'reconciled')
                ->count();

            $snapshot = [
                'posting_audit' => $postingAudit,
                'control_reconciliation' => $control,
                'balance_sheet_difference_kobo' => $balanceSheet['difference_kobo'],
                'unreconciled_bank_statements' => $unreconciledStatements,
            ];
            $difference = $postingAudit['gaps']
                + $postingAudit['invalid']
                + $control['total_difference_kobo']
                + abs($balanceSheet['difference_kobo'])
                + $unreconciledStatements;

            if ($difference !== 0) {
                throw new \DomainException(
                    'The period cannot close until posting, subledger, inventory, statement and balance-sheet differences are zero.',
                );
            }

            $batch = AccountingCloseBatch::query()->updateOrCreate(
                ['accounting_period_id' => $locked->getKey()],
                [
                    'status' => 'locked',
                    'reconciliation_snapshot' => $snapshot,
                    'prepared_by_account_id' => $actor->getKey(),
                    'approved_by_account_id' => $actor->getKey(),
                    'prepared_at' => now(),
                    'approved_at' => now(),
                    'locked_at' => now(),
                    'notes' => $notes,
                ],
            );

            JournalEntry::query()
                ->where('accounting_period_id', $locked->getKey())
                ->where('status', 'posted')
                ->update([
                    'accounting_close_batch_id' => $batch->getKey(),
                    'locked_by_account_id' => $actor->getKey(),
                    'locked_at' => now(),
                    'updated_at' => now(),
                ]);

            $locked->forceFill([
                'status' => PeriodStatus::Locked,
                'closed_by_account_id' => $actor->getKey(),
                'closed_at' => now(),
                'locked_by_account_id' => $actor->getKey(),
                'locked_at' => now(),
            ])->save();

            return $batch;
        }, 3);
    }
}
