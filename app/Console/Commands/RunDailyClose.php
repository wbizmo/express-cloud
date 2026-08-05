<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Operations\DailyCloseWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class RunDailyClose extends Command
{
    protected $signature = 'operations:daily-close {--date= : Business date in YYYY-MM-DD format}';

    protected $description = 'Run the idempotent daily-close reports, reconciliation and notification workflow.';

    public function handle(DailyCloseWorkflow $workflow): int
    {
        $date = (string) ($this->option('date') ?: CarbonImmutable::now((string) config('operations.report_timezone'))->subDay()->toDateString());
        $run = $workflow->run($date);
        $this->line(json_encode([
            'id' => (string) $run->getKey(),
            'business_date' => (string) $run->business_date,
            'status' => $run->status,
            'attempt_count' => $run->attempt_count,
        ], JSON_THROW_ON_ERROR));

        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
