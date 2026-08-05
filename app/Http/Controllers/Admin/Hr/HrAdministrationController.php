<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hr;

use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\JobRole;
use App\Models\PayrollRun;
use App\Models\PerformanceReview;
use App\Services\Hr\HrAdministrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class HrAdministrationController
{
    public function __construct(private HrAdministrationService $hr) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $branchIds = $actor->is_allowed_all_branches
            ? null
            : $actor->branches()->pluck('branches.id');

        /** @var view-string $viewName */
        $viewName = 'admin.hr.index';

        return view($viewName, [
            'employees' => Employee::query()
                ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds))
                ->orderBy('last_name')->orderBy('first_name')
                ->cursorPaginate(config('pagination.default', 10))->withQueryString(),
            'departments' => Department::query()
                ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
                ->where('status', 'active')->orderBy('name')->get(),
            'roles' => JobRole::query()->where('status', 'active')->orderBy('title')->get(),
            'branches' => Branch::query()
                ->when($branchIds !== null, static fn ($query) => $query->whereIn('id', $branchIds))
                ->where('status', 'active')->orderBy('name')->get(),
            'attendance' => AttendanceRecord::query()->latest('work_date')->limit(20)->get(),
            'holidays' => Holiday::query()->where('is_active', true)->orderBy('holiday_date')->limit(20)->get(),
            'reviews' => PerformanceReview::query()->latest('period_ends_on')->limit(20)->get(),
            'payrollRuns' => PayrollRun::query()->latest('period_ends_on')->limit(20)->get(),
        ]);
    }

    public function employee(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:employees,employee_code'],
            'account_id' => ['nullable', 'ulid', 'unique:employees,account_id'],
            'branch_id' => ['nullable', 'ulid'],
            'department_id' => ['nullable', 'ulid'],
            'job_role_id' => ['nullable', 'ulid'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'hired_on' => ['nullable', 'date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,temporary,intern'],
        ]);
        $key = (string) $validated['idempotency_key'];
        unset($validated['idempotency_key']);
        $this->hr->createEmployee($validated, $actor, $key);

        return back()->with('status', 'Employee profile created.');
    }

    public function assign(Request $request, Employee $employee): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'branch_id' => ['nullable', 'ulid'],
            'department_id' => ['nullable', 'ulid'],
            'job_role_id' => ['nullable', 'ulid'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'memo' => ['required', 'string', 'max:2000'],
        ]);
        $this->hr->assign(
            $employee,
            $actor,
            isset($validated['branch_id']) ? (string) $validated['branch_id'] : null,
            isset($validated['department_id']) ? (string) $validated['department_id'] : null,
            isset($validated['job_role_id']) ? (string) $validated['job_role_id'] : null,
            (string) $validated['starts_on'],
            isset($validated['ends_on']) ? (string) $validated['ends_on'] : null,
            (string) $validated['memo'],
        );

        return back()->with('status', 'Employee assignment updated.');
    }

    public function attendance(Request $request, Employee $employee): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'work_date' => ['required', 'date'],
            'clocked_in_at' => ['nullable', 'date'],
            'clocked_out_at' => ['nullable', 'date'],
            'status' => ['required', 'in:present,absent,late,leave,holiday'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->hr->recordAttendance(
            $employee,
            $actor,
            (string) $validated['work_date'],
            isset($validated['clocked_in_at']) ? (string) $validated['clocked_in_at'] : null,
            isset($validated['clocked_out_at']) ? (string) $validated['clocked_out_at'] : null,
            (string) $validated['status'],
            isset($validated['notes']) ? (string) $validated['notes'] : null,
        );

        return back()->with('status', 'Attendance recorded.');
    }

    public function holiday(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'holiday_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'ulid'],
            'is_paid' => ['nullable', 'boolean'],
        ]);
        $this->hr->addHoliday(
            $actor,
            (string) $validated['name'],
            (string) $validated['holiday_date'],
            isset($validated['branch_id']) ? (string) $validated['branch_id'] : null,
            (bool) ($validated['is_paid'] ?? false),
        );

        return back()->with('status', 'Holiday calendar updated.');
    }

    public function review(Request $request, Employee $employee): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'period_starts_on' => ['required', 'date'],
            'period_ends_on' => ['required', 'date', 'after_or_equal:period_starts_on'],
            'score' => ['required', 'integer', 'between:1,100'],
            'metrics' => ['nullable', 'array'],
            'summary' => ['required', 'string', 'max:5000'],
            'development_plan' => ['nullable', 'string', 'max:5000'],
        ]);
        /** @var array<string, int|float|string> $metrics */
        $metrics = $validated['metrics'] ?? [];
        $this->hr->review(
            $employee,
            $actor,
            (string) $validated['period_starts_on'],
            (string) $validated['period_ends_on'],
            (int) $validated['score'],
            $metrics,
            (string) $validated['summary'],
            isset($validated['development_plan']) ? (string) $validated['development_plan'] : null,
        );

        return back()->with('status', 'Performance review recorded.');
    }

    public function payroll(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'period_starts_on' => ['required', 'date'],
            'period_ends_on' => ['required', 'date', 'after_or_equal:period_starts_on'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.employee_id' => ['required', 'ulid'],
            'lines.*.gross_kobo' => ['required', 'integer', 'min:0'],
            'lines.*.deductions_kobo' => ['required', 'integer', 'min:0'],
        ]);
        /** @var list<array{employee_id: string, gross_kobo: int, deductions_kobo: int, components?: array<string, mixed>}> $lines */
        $lines = $validated['lines'];
        $this->hr->preparePayroll(
            $actor,
            (string) $validated['period_starts_on'],
            (string) $validated['period_ends_on'],
            $lines,
        );

        return back()->with('status', 'Payroll draft prepared.');
    }
}
