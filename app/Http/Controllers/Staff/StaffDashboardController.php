<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Models\Account;
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

        return view('staff.dashboard', [
            'account' => $account,
            ...$this->dashboard->for($account),
        ]);
    }
}
