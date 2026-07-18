<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Enums\Operations\AdminNotificationType;
use App\Models\Account;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Services\Organisation\AuditLogger;
use App\Services\Reports\StaffPerformanceReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class StaffPerformanceController
{
    public function __construct(
        private StaffPerformanceReport $report,
        private AuditLogger $audit,
    ) {}

    public function __invoke(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();
        $branchId = $request->filled('branch') ? $request->string('branch')->toString() : null;

        $branches = Branch::query()
            ->where('status', 'active')
            ->when(! $actor->is_allowed_all_branches, static fn ($query) => $query->whereIn('id', $actor->branches()->select('branches.id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($branchId !== null && ! $branches->contains('id', $branchId)) {
            abort(403, 'You do not have access to this branch.');
        }

        $rows = $this->report->run($from, $to, $branchId);
        $totalRevenue = (int) $rows->sum('revenue_kobo');
        $averageRevenue = $rows->count() > 0 ? (int) round($totalRevenue / $rows->count()) : 0;

        return view('admin.reports.staff-performance', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'selectedBranch' => $branchId,
            'branches' => $branches,
            'summary' => [
                'active_sellers' => $rows->count(),
                'sales_count' => (int) $rows->sum('sales_count'),
                'revenue_kobo' => $totalRevenue,
                'outstanding_kobo' => (int) $rows->sum('outstanding_kobo'),
                'average_revenue_kobo' => $averageRevenue,
            ],
        ]);
    }

    public function announce(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'branch_id' => ['nullable', 'string', 'exists:branches,id'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $branch = null;
        if (! empty($validated['branch_id'])) {
            $branch = Branch::query()
                ->whereKey($validated['branch_id'])
                ->when(! $actor->is_allowed_all_branches, static fn ($query) => $query->whereIn('id', $actor->branches()->select('branches.id')))
                ->firstOrFail();
        }

        $notification = AdminNotification::query()->create([
            'notification_type' => AdminNotificationType::Operational,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'entity_type' => 'workforce_announcement',
            'entity_id' => (string) $actor->getKey(),
            'branch_id' => $branch?->getKey(),
            'occurred_at' => now(),
        ]);

        $this->audit->record($request, 'workforce.announcement-created', 'admin_notification', $notification, $branch, after: [
            'title' => $notification->title,
            'branch_id' => $notification->branch_id,
        ]);

        return back()->with('status', 'Workforce announcement published.');
    }
}
