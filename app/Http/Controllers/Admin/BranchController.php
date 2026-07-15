<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Organisation\BranchStatus;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Models\Branch;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class BranchController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::query()
                ->orderByDesc('is_head_office')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function store(
        StoreBranchRequest $request,
    ): RedirectResponse {
        $branch = Branch::query()->create([
            ...$request->safe()->only([
                'name',
                'address',
                'phone',
                'is_head_office',
            ]),
            'code' => Str::upper(
                $request->string('code')->trim()->toString(),
            ),
            'status' => BranchStatus::Active,
        ]);

        $this->audit->record(
            $request,
            'branch.created',
            'branch',
            $branch,
            $branch,
            after: $branch->toArray(),
        );

        return back()->with('status', 'Branch created.');
    }

    public function deactivate(
        Request $request,
        Branch $branch,
    ): RedirectResponse {
        $before = $branch->toArray();

        $branch->forceFill([
            'status' => BranchStatus::Inactive,
        ])->save();

        $this->audit->record(
            $request,
            'branch.deactivated',
            'branch',
            $branch,
            $branch,
            before: $before,
            after: $branch->fresh()?->toArray(),
        );

        return back()->with('status', 'Branch deactivated.');
    }
}
