<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccrualPosting;
use App\Models\AccrualSchedule;
use App\Models\OperationRequest;
use App\Services\Operations\CommandBoundary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class AccrualService
{
    public function __construct(
        private CommandBoundary $commands,
        private JournalPoster $journals,
    ) {}

    public function create(
        Account $actor,
        string $idempotencyKey,
        string $scheduleType,
        int $totalKobo,
        int $periodCount,
        string $startsOn,
        string $endsOn,
        string $expenseAccountId,
        string $balanceSheetAccountId,
        string $memo,
        ?string $branchId = null,
    ): AccrualSchedule {
        if (! in_array($scheduleType, ['accrual', 'prepayment'], true)) {
            throw new \InvalidArgumentException('Schedule type must be accrual or prepayment.');
        }
        if ($totalKobo <= 0 || $periodCount <= 0) {
            throw new \InvalidArgumentException('Accrual schedules require positive value and periods.');
        }

        $payload = compact(
            'scheduleType',
            'totalKobo',
            'periodCount',
            'startsOn',
            'endsOn',
            'expenseAccountId',
            'balanceSheetAccountId',
            'memo',
            'branchId',
        );
        $result = $this->commands->execute(
            'accounting.accrual.create',
            $idempotencyKey,
            $payload,
            $actor,
            $branchId,
            static fn (OperationRequest $operation): AccrualSchedule => AccrualSchedule::query()->create([
                'schedule_number' => 'ACR-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'branch_id' => $branchId,
                'expense_ledger_account_id' => $expenseAccountId,
                'balance_sheet_ledger_account_id' => $balanceSheetAccountId,
                'created_by_account_id' => $actor->getKey(),
                'operation_request_id' => $operation->getKey(),
                'schedule_type' => $scheduleType,
                'total_kobo' => $totalKobo,
                'period_count' => $periodCount,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'posted_periods' => 0,
                'status' => 'active',
                'memo' => $memo,
            ]),
        );

        if (! $result instanceof AccrualSchedule) {
            throw new \LogicException('The accrual command returned an invalid result.');
        }

        return $result;
    }

    public function postDue(AccrualSchedule $schedule, string $throughDate): int
    {
        return DB::transaction(function () use ($schedule, $throughDate): int {
            /** @var AccrualSchedule $locked */
            $locked = AccrualSchedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'active') {
                return 0;
            }

            $through = CarbonImmutable::parse($throughDate)->endOfDay();
            $posted = 0;
            for ($period = 1; $period <= $locked->period_count; $period++) {
                $postingDate = CarbonImmutable::parse($locked->starts_on)
                    ->addMonthsNoOverflow($period - 1)
                    ->endOfMonth();
                if ($postingDate->greaterThan($through)) {
                    break;
                }
                if (AccrualPosting::query()
                    ->where('accrual_schedule_id', $locked->getKey())
                    ->where('period_number', $period)
                    ->exists()) {
                    continue;
                }

                $amount = $locked->periodicAmountKobo($period);
                $journal = $this->journals->post(
                    $postingDate,
                    $locked->memo.' period '.$period,
                    [
                        [
                            'account_id' => (string) $locked->expense_ledger_account_id,
                            'debit_kobo' => $amount,
                        ],
                        [
                            'account_id' => (string) $locked->balance_sheet_ledger_account_id,
                            'credit_kobo' => $amount,
                        ],
                    ],
                    $locked->branch_id,
                    $locked->created_by_account_id,
                    AccrualSchedule::class,
                    (string) $locked->getKey(),
                    'period-'.$period,
                    null,
                    null,
                    'accrual',
                );
                AccrualPosting::query()->create([
                    'accrual_schedule_id' => $locked->getKey(),
                    'journal_entry_id' => $journal->getKey(),
                    'period_number' => $period,
                    'posting_date' => $postingDate->toDateString(),
                    'amount_kobo' => $amount,
                ]);
                $posted++;
            }

            $totalPosted = AccrualPosting::query()
                ->where('accrual_schedule_id', $locked->getKey())
                ->count();
            $locked->forceFill([
                'posted_periods' => $totalPosted,
                'status' => $totalPosted >= $locked->period_count ? 'completed' : 'active',
            ])->save();

            return $posted;
        }, 3);
    }
}
