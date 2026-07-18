<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyReportRun;
use App\Services\Reports\DailyOperationsDigest;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

final class GenerateDailyOperationsReports extends Command
{
    protected $signature = 'reports:daily-operations {date?}';

    protected $description = 'Generate separate previous-day operational spreadsheets and HTML summary.';

    public function handle(DailyOperationsDigest $digest): int
    {
        $date = CarbonImmutable::parse($this->argument('date') ?: 'yesterday')->startOfDay();
        $run = DailyReportRun::query()->firstOrCreate(['report_date' => $date->toDateString()], ['status' => 'pending']);
        $run->update(['status' => 'running', 'started_at' => now(), 'failure_message' => null]);
        try {
            $r = $digest->generate($date);
            $run->update(['status' => 'completed', 'generated_files' => $r['files'], 'summary_html' => $r['summary_html'], 'completed_at' => now()]);
            $this->info('Generated '.count($r['files']).' report files for '.$date->toDateString());

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $run->update(['status' => 'failed', 'failure_message' => $e->getMessage(), 'completed_at' => now()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
