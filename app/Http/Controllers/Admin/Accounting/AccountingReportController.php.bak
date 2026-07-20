<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\LedgerAccount;
use App\Queries\Accounting\FinancialReports;
use App\Services\Reports\Exports\TabularExport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class AccountingReportController
{
    private const REPORTS = [
        'trial-balance' => 'Trial Balance',
        'income-statement' => 'Profit & Loss',
        'balance-sheet' => 'Balance Sheet',
        'cash-flow' => 'Cash Flow Summary',
        'general-ledger' => 'General Ledger',
    ];

    public function __construct(
        private FinancialReports $reports,
        private TabularExport $export,
    ) {}

    public function index(Request $request): View
    {
        $report = $request->string('report', 'trial-balance')->toString();
        $report = array_key_exists($report, self::REPORTS)
            ? $report
            : 'trial-balance';

        $from = $request->string(
            'from',
            now()->startOfYear()->toDateString(),
        )->toString();
        $to = $request->string('to', now()->toDateString())->toString();
        $accountId = $request->string('account_id')->toString();

        return view('admin.accounting.reports', [
            'reportTypes' => self::REPORTS,
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'accountId' => $accountId,
            'accounts' => LedgerAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'data' => $this->buildReport($report, $from, $to, $accountId),
        ]);
    }

    public function export(Request $request): mixed
    {
        $report = $request->string('report', 'trial-balance')->toString();
        $report = array_key_exists($report, self::REPORTS)
            ? $report
            : 'trial-balance';

        $from = $request->string(
            'from',
            now()->startOfYear()->toDateString(),
        )->toString();
        $to = $request->string('to', now()->toDateString())->toString();
        $accountId = $request->string('account_id')->toString();
        $format = $request->string('format', 'csv')->toString();

        [$headings, $rows] = $this->tabulate(
            $report,
            $this->buildReport($report, $from, $to, $accountId),
        );

        $filename = str($report).'-'.now()->format('Ymd-His');
        $title = self::REPORTS[$report];

        return match ($format) {
            'xlsx' => $this->export->excel(
                "{$filename}.xlsx",
                $title,
                $headings,
                $rows,
            ),
            'pdf' => $this->export->pdf(
                "{$filename}.pdf",
                $title,
                $headings,
                $rows,
            ),
            default => $this->export->csv(
                "{$filename}.csv",
                $headings,
                $rows,
            ),
        };
    }

    /** @return array<string, mixed> */
    private function buildReport(
        string $report,
        string $from,
        string $to,
        string $accountId,
    ): array {
        return match ($report) {
            'income-statement' => $this->reports->incomeStatement(
                $from,
                $to,
            ),
            'balance-sheet' => $this->reports->balanceSheet($to),
            'cash-flow' => $this->reports->cashFlowSummary($from, $to),
            'general-ledger' => [
                'lines' => $accountId !== ''
                    ? $this->reports->generalLedger(
                        $accountId,
                        $from,
                        $to,
                    )
                    : collect(),
            ],
            default => ['lines' => $this->reports->trialBalance(
                $from,
                $to,
            )],
        };
    }

    /**
     * Flatten whichever report shape was built into a plain
     * headings/rows table, so the same export service can serve every
     * report type without a bespoke exporter per report.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: list<string>, 1: list<array<int, scalar|null>>}
     */
    private function tabulate(string $report, array $data): array
    {
        return match ($report) {
            'income-statement' => [
                ['Type', 'Code', 'Account', 'Amount (₦)'],
                [
                    ...collect($data['revenue'])->map(static fn ($r) => [
                        'Revenue', $r->code, $r->name,
                        number_format(
                            ($r->credit_kobo - $r->debit_kobo) / 100,
                            2,
                        ),
                    ]),
                    ...collect($data['expense'])->map(static fn ($r) => [
                        'Expense', $r->code, $r->name,
                        number_format(
                            ($r->debit_kobo - $r->credit_kobo) / 100,
                            2,
                        ),
                    ]),
                    [
                        'Net', '', 'Net Profit / (Loss)',
                        number_format($data['net_profit_kobo'] / 100, 2),
                    ],
                ],
            ],
            'balance-sheet' => [
                ['Section', 'Code', 'Account', 'Amount (₦)'],
                [
                    ...collect($data['assets'])->map(static fn ($r) => [
                        'Asset', $r->code, $r->name,
                        number_format(
                            ($r->debit_kobo - $r->credit_kobo) / 100,
                            2,
                        ),
                    ]),
                    ...collect($data['liabilities'])->map(static fn ($r) => [
                        'Liability', $r->code, $r->name,
                        number_format(
                            ($r->credit_kobo - $r->debit_kobo) / 100,
                            2,
                        ),
                    ]),
                    ...collect($data['equity'])->map(static fn ($r) => [
                        'Equity', $r->code, $r->name,
                        number_format(
                            ($r->credit_kobo - $r->debit_kobo) / 100,
                            2,
                        ),
                    ]),
                    [
                        'Equity', '', 'Retained Earnings',
                        number_format(
                            $data['retained_earnings_kobo'] / 100,
                            2,
                        ),
                    ],
                ],
            ],
            'cash-flow' => [
                ['Source', 'Net Movement (₦)'],
                [
                    ...collect($data['by_source'])->map(static fn ($r) => [
                        $r->source_type ?? 'Uncategorised',
                        number_format($r->net_kobo / 100, 2),
                    ]),
                    ['Opening Cash', number_format(
                        $data['opening_kobo'] / 100,
                        2,
                    )],
                    ['Closing Cash', number_format(
                        $data['closing_kobo'] / 100,
                        2,
                    )],
                ],
            ],
            'general-ledger' => [
                ['Date', 'Journal #', 'Memo', 'Debit (₦)', 'Credit (₦)'],
                collect($data['lines'])->map(static fn ($r) => [
                    $r->entry_date,
                    $r->journal_number,
                    $r->description ?: $r->memo,
                    number_format($r->debit_kobo / 100, 2),
                    number_format($r->credit_kobo / 100, 2),
                ])->all(),
            ],
            default => [
                ['Code', 'Account', 'Type', 'Debit (₦)', 'Credit (₦)'],
                collect($data['lines'])->map(static fn ($r) => [
                    $r->code,
                    $r->name,
                    ucfirst($r->type),
                    number_format($r->debit_kobo / 100, 2),
                    number_format($r->credit_kobo / 100, 2),
                ])->all(),
            ],
        };
    }
}
