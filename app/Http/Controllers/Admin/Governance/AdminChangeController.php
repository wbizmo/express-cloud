<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Governance;

use App\Models\Account;
use App\Models\AdminChangeRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Warehouse;
use App\Services\Governance\AdminChangeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class AdminChangeController
{
    /** @var array<string, class-string<Model>> */
    private const RESOURCES = [
        'branch' => Branch::class,
        'warehouse' => Warehouse::class,
        'customer' => Customer::class,
        'payment_method' => PaymentMethod::class,
        'department' => Department::class,
        'employee' => Employee::class,
    ];

    public function __construct(private AdminChangeService $changes) {}

    public function index(): View
    {
        /** @var view-string $viewName */
        $viewName = 'admin.governance.changes';

        return view($viewName, [
            'requests' => AdminChangeRequest::query()
                ->orderByDesc('created_at')
                ->cursorPaginate(config('pagination.default', 10)),
            'resources' => array_keys(self::RESOURCES),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'resource' => ['required', 'in:'.implode(',', array_keys(self::RESOURCES))],
            'resource_id' => ['nullable', 'ulid'],
            'action' => ['required', 'in:create,update,deactivate,reactivate'],
            'payload' => ['required', 'array'],
            'memo' => ['required', 'string', 'max:3000'],
        ]);
        $this->changes->submit(
            self::RESOURCES[(string) $validated['resource']],
            isset($validated['resource_id']) ? (string) $validated['resource_id'] : null,
            (string) $validated['action'],
            $validated['payload'],
            $actor,
            (string) $validated['memo'],
        );

        return back()->with('status', 'Administrative change submitted for approval.');
    }

    public function decide(Request $request, AdminChangeRequest $change): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['required', 'string', 'max:3000'],
        ]);
        $this->changes->decide(
            $change,
            $actor,
            $validated['decision'] === 'approve',
            (string) $validated['note'],
        );

        return back()->with('status', 'Administrative change decision recorded.');
    }
}
