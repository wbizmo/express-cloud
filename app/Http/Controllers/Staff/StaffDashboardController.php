<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Models\Account;
use App\Models\AdminNotification;
use App\Services\Dashboard\StaffDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class StaffDashboardController
{
    public function __construct(private StaffDashboardData $dashboard) {}

    public function __invoke(Request $request): View
    {
        /** @var Account $account */
        $account = $request->user();
        $branchIds = $account->branches()->pluck('branches.id');

        return view('staff.dashboard', [
            'account' => $account,
            'workforceAnnouncements' => AdminNotification::query()
                ->where('entity_type', 'workforce_announcement')
                ->where(static fn ($query) => $query
                    ->whereNull('branch_id')
                    ->orWhereIn('branch_id', $branchIds))
                ->orderByDesc('occurred_at')
                ->limit(5)
                ->get(['id', 'title', 'message', 'branch_id', 'occurred_at']),
            ...$this->dashboard->for($account),
        ]);
    }
}
