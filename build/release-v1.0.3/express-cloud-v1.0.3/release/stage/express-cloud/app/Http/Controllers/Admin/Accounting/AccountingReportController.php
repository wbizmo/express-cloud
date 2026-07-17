<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Models\LedgerAccount;
use App\Queries\Accounting\FinancialReports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class AccountingReportController
{
    public function __construct(private FinancialReports $reports) {}

    public function index(Request $request): View
    {
        $from = $request->string(
            'from',
            now()->startOfYear()->toDateString(),
        )->toString();
        $to = $request->string(
            'to',
            now()->toDateString(),
        )->toString();

        $trial = $this->reports->trialBalance($from, $to);

        return view('admin.accounting.reports', [
            'from' => $from,
            'to' => $to,
            'trial' => $trial,
            'accounts' => LedgerAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
        ]);
    }
}
