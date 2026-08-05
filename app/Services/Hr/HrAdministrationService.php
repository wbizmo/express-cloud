<?php

declare(strict_types=1);

namespace App\Services\Hr;

use App\Models\Account;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Holiday;
use App\Models\OperationRequest;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Models\PerformanceReview;
use App\Services\Operations\CommandBoundary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class HrAdministrationService
{
    public function __construct(private CommandBoundary $commands) {}

    /** @param array<string, mixed> $attributes */
    public function createEmployee(
        array $attributes,
        Account $actor,
        string $idempotencyKey,
    ): Employee {
        $result = $this->commands->execute(
            'hr.employee.create',
            $idempotencyKey,
            $attributes,
            $actor,
            isset($attributes['branch_id']) ? (string) $attributes['branch_id'] : null,
            static function (OperationRequest $operation) use ($attributes, $actor): Employee {
                return Employee::query()->create([
                    ...$attributes,
                    'employee_code' => $attributes['employee_code']
                        ?? 'EMP-'.Str::upper(Str::random(8)),
                    'created_by_account_id' => $actor->getKey(),
                    'status' => $attributes['status'] ?? 'active',
                ]);
            },
        );

        if (! $result instanceof Employee) {
            throw new \LogicException('The employee command returned an invalid result.');
        }

        return $result;
    }

    public function assign(
        Employee $employee,
        Account $actor,
        ?string $branchId,
        ?string $departmentId,
        ?string $jobRoleId,
        string $startsOn,
        ?string $endsOn,
        string $memo,
    ): EmployeeAssignment {
        return DB::transaction(function () use (
            $employee, $actor, $branchId, $departmentId, $jobRoleId, $startsOn, $endsOn, $memo,
        ): EmployeeAssignment {
            EmployeeAssignment::query()
                ->where('employee_id', $employee->getKey())
                ->whereNull('ends_on')
                ->update(['ends_on' => CarbonImmutable::parse($startsOn)->subDay()->toDateString()]);
            $assignment = EmployeeAssignment::query()->create([
                'employee_id' => $employee->getKey(),
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'job_role_id' => $jobRoleId,
                'starts_on' => CarbonImmutable::parse($startsOn)->toDateString(),
                'ends_on' => $endsOn !== null ? CarbonImmutable::parse($endsOn)->toDateString() : null,
                'assigned_by_account_id' => $actor->getKey(),
                'memo' => trim($memo),
            ]);
            $employee->forceFill([
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'job_role_id' => $jobRoleId,
            ])->save();

            return $assignment;
        }, 3);
    }

    public function recordAttendance(
        Employee $employee,
        Account $actor,
        string $workDate,
        ?string $clockIn,
        ?string $clockOut,
        string $status,
        ?string $notes,
    ): AttendanceRecord {
        $in = $clockIn !== null ? CarbonImmutable::parse($clockIn) : null;
        $out = $clockOut !== null ? CarbonImmutable::parse($clockOut) : null;
        if ($in !== null && $out !== null && $out->lessThan($in)) {
            throw new \DomainException('Clock-out cannot be earlier than clock-in.');
        }

        return AttendanceRecord::query()->updateOrCreate(
            ['employee_id' => $employee->getKey(), 'work_date' => CarbonImmutable::parse($workDate)->toDateString()],
            [
                'branch_id' => $employee->branch_id,
                'clocked_in_at' => $in,
                'clocked_out_at' => $out,
                'worked_minutes' => $in !== null && $out !== null ? (int) $in->diffInMinutes($out) : 0,
                'status' => $status,
                'recorded_by_account_id' => $actor->getKey(),
                'notes' => $notes,
            ],
        );
    }

    /** @param array<string, int|float|string> $metrics */
    public function review(
        Employee $employee,
        Account $reviewer,
        string $startsOn,
        string $endsOn,
        int $score,
        array $metrics,
        string $summary,
        ?string $plan,
    ): PerformanceReview {
        if ($score < 1 || $score > 100 || trim($summary) === '') {
            throw new \DomainException('Performance reviews require a score from 1 to 100 and a summary.');
        }

        return PerformanceReview::query()->create([
            'employee_id' => $employee->getKey(),
            'reviewer_account_id' => $reviewer->getKey(),
            'period_starts_on' => CarbonImmutable::parse($startsOn)->toDateString(),
            'period_ends_on' => CarbonImmutable::parse($endsOn)->toDateString(),
            'score' => $score,
            'metrics' => $metrics,
            'summary' => trim($summary),
            'development_plan' => $plan,
            'status' => 'completed',
        ]);
    }

    /** @param list<array{employee_id: string, gross_kobo: int, deductions_kobo: int, components?: array<string, mixed>}> $lines */
    public function preparePayroll(
        Account $actor,
        string $startsOn,
        string $endsOn,
        array $lines,
    ): PayrollRun {
        if (! config('hr.payroll_enabled', false)) {
            throw new \DomainException('Payroll is disabled. Enable HR_PAYROLL_ENABLED only after statutory configuration is complete.');
        }

        return DB::transaction(function () use ($actor, $startsOn, $endsOn, $lines): PayrollRun {
            $run = PayrollRun::query()->create([
                'run_number' => 'PAY-'.now()->format('Ym').'-'.Str::upper(Str::random(6)),
                'period_starts_on' => CarbonImmutable::parse($startsOn)->toDateString(),
                'period_ends_on' => CarbonImmutable::parse($endsOn)->toDateString(),
                'status' => 'draft',
                'prepared_by_account_id' => $actor->getKey(),
            ]);
            $gross = 0;
            $deductions = 0;
            foreach ($lines as $line) {
                $lineGross = max(0, (int) $line['gross_kobo']);
                $lineDeductions = max(0, (int) $line['deductions_kobo']);
                if ($lineDeductions > $lineGross) {
                    throw new \DomainException('Payroll deductions cannot exceed gross pay.');
                }
                PayrollRunLine::query()->create([
                    'payroll_run_id' => $run->getKey(),
                    'employee_id' => $line['employee_id'],
                    'gross_kobo' => $lineGross,
                    'deductions_kobo' => $lineDeductions,
                    'net_kobo' => $lineGross - $lineDeductions,
                    'components' => $line['components'] ?? null,
                ]);
                $gross += $lineGross;
                $deductions += $lineDeductions;
            }
            $run->forceFill([
                'gross_total_kobo' => $gross,
                'deduction_total_kobo' => $deductions,
                'net_total_kobo' => $gross - $deductions,
            ])->save();

            return $run;
        }, 3);
    }

    public function addHoliday(
        Account $actor,
        string $name,
        string $date,
        ?string $branchId,
        bool $paid,
    ): Holiday {
        return Holiday::query()->updateOrCreate(
            ['branch_id' => $branchId, 'holiday_date' => CarbonImmutable::parse($date)->toDateString()],
            [
                'name' => trim($name),
                'is_paid' => $paid,
                'is_active' => true,
                'created_by_account_id' => $actor->getKey(),
            ],
        );
    }
}
