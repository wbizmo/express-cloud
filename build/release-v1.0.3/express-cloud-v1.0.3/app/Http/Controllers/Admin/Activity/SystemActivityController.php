<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Models\Account;
use App\Queries\Activity\SystemActivityQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class SystemActivityController
{
    public function __construct(
        private SystemActivityQuery $activity,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('admin.activity.index', [
            'entries' => $this->activity->run(
                $request->filled('actor')
                    ? $request->string('actor')->toString()
                    : null,
                $request->filled('entity_type')
                    ? $request->string('entity_type')->toString()
                    : null,
                $request->date('from')?->toDateString(),
                $request->date('to')?->toDateString(),
            ),
            'actors' => Account::query()
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
