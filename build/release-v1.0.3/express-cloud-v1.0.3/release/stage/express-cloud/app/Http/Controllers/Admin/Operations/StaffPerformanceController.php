<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Models\Branch;
use App\Services\Reports\StaffPerformanceReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class StaffPerformanceController
{
    public function __construct(
        private StaffPerformanceReport $report,
    ) {}

    public function __invoke(Request $request): View
    {
        $from = $request->date('from')?->toDateString()
            ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString()
            ?? today()->toDateString();
        $branchId = $request->filled('branch')
            ? $request->string('branch')->toString()
            : null;

        return view('admin.reports.staff-performance', [
            'rows' => $this->report->run(
                $from,
                $to,
                $branchId,
            ),
            'from' => $from,
            'to' => $to,
            'selectedBranch' => $branchId,
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
