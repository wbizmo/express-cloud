<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AccrualSchedule;
use App\Services\Accounting\AccrualService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class PostAccrualSchedules extends Command
{
    protected $signature = 'accounting:accruals-post {through_date}';

    protected $description = 'Post all due accrual and prepayment schedule periods idempotently.';

    public function handle(AccrualService $accruals): int
    {
        $through = CarbonImmutable::parse((string) $this->argument('through_date'));
        $posted = 0;

        AccrualSchedule::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($schedules) use ($accruals, $through, &$posted): void {
                foreach ($schedules as $schedule) {
                    /** @var AccrualSchedule $schedule */
                    $posted += $accruals->postDue($schedule, $through->toDateString());
                }
            });

        $this->info("Posted {$posted} due accrual/prepayment period(s).");

        return self::SUCCESS;
    }
}
