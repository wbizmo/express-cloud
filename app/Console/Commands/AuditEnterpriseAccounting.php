<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Accounting\EnterpriseFinancialStatements;
use App\Services\Accounting\FinancialPostingReconciler;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class AuditEnterpriseAccounting extends Command
{
    protected $signature = 'accounting:enterprise-audit
        {--date= : Reporting date in YYYY-MM-DD format}
        {--branch= : Optional branch ULID}
        {--fail-on-gap : Return a failing exit code when a difference exists}';

    protected $description = 'Verify financial postings, statements, customer/supplier controls and inventory valuation.';

    public function handle(
        FinancialPostingReconciler $postings,
        EnterpriseFinancialStatements $statements,
    ): int {
        $asOf = CarbonImmutable::parse((string) ($this->option('date') ?: today()->toDateString()));
        $branchId = $this->option('branch');
        $branchId = is_string($branchId) && $branchId !== '' ? $branchId : null;

        $posting = $postings->audit();
        $control = $statements->controlReconciliation($asOf, $branchId);
        $balanceSheet = $statements->balanceSheet($asOf, $branchId);
        $trialBalance = $statements->trialBalance($asOf, $branchId);
        $trialDifference = abs(
            (int) $trialBalance->sum('debit_kobo')
            - (int) $trialBalance->sum('credit_kobo'),
        );

        $rows = [
            ['Posting gaps', $posting['gaps']],
            ['Invalid postings', $posting['invalid']],
            ['Receivables difference (kobo)', $control['accounts_receivable_difference_kobo']],
            ['Payables difference (kobo)', $control['accounts_payable_difference_kobo']],
            ['Inventory difference (kobo)', $control['inventory_difference_kobo']],
            ['Trial balance difference (kobo)', $trialDifference],
            ['Balance sheet difference (kobo)', $balanceSheet['difference_kobo']],
        ];
        $this->table(['Control', 'Difference'], $rows);

        $difference = (int) $posting['gaps']
            + (int) $posting['invalid']
            + abs((int) $control['total_difference_kobo'])
            + $trialDifference
            + abs((int) $balanceSheet['difference_kobo']);

        if ($difference === 0) {
            $this->info('Enterprise accounting audit completed with zero differences.');

            return self::SUCCESS;
        }

        $this->error("Enterprise accounting audit found {$difference} aggregate difference units.");

        return $this->option('fail-on-gap') ? self::FAILURE : self::SUCCESS;
    }
}
