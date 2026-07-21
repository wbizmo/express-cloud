<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class AuditLogController
{
    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();

        $sort = $request->string('sort', 'occurred_at')->toString();
        $sort = in_array($sort, ['occurred_at', 'action', 'entity_type'], true)
            ? $sort
            : 'occurred_at';
        $direction = $request->string('direction', 'desc')->toString();
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $logs = AuditLog::query()
            ->with(['branch:id,name', 'actor:id,first_name,last_name'])
            ->when(
                ! $actor->is_allowed_all_branches,
                fn ($query) => $query->whereIn(
                    'branch_id',
                    $actor->branches()->select('branches.id'),
                ),
            )
            ->when(
                $request->filled('branch'),
                fn ($query) => $query->where(
                    'branch_id',
                    $request->string('branch')->toString(),
                ),
            )
            ->when(
                $request->filled('actor'),
                fn ($query) => $query->where(
                    'actor_account_id',
                    $request->string('actor')->toString(),
                ),
            )
            ->when(
                $request->filled('entity_type'),
                fn ($query) => $query->where(
                    'entity_type',
                    $request->string('entity_type')->toString(),
                ),
            )
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate(
                    'occurred_at',
                    '>=',
                    $request->date('from'),
                ),
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate(
                    'occurred_at',
                    '<=',
                    $request->date('to'),
                ),
            )
            ->orderBy($sort, $direction)
            ->paginate(config('pagination.default', 10))
            ->withQueryString();

        return view('admin.reports.audit-log', [
            'logs' => $logs,
            'sort' => $sort,
            'direction' => $direction,
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'entityTypes' => AuditLog::query()
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type'),
        ]);
    }
}
