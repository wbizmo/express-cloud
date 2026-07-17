<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Insights\LisaInsightEngine;
use Illuminate\Console\Command;

final class GenerateLisaInsights extends Command
{
    protected $signature = 'lisa:generate {--from=} {--to=}';

    protected $description = 'Generate permission-safe business insights from summarized operational data.';

    public function handle(LisaInsightEngine $engine): int
    {
        $from = (string) ($this->option('from') ?: now()->subDays(30)->toDateString());
        $to = (string) ($this->option('to') ?: today()->toDateString());
        $count = $engine->generate($from, $to);
        $this->info(sprintf('Generated or refreshed %d Lisa insights.', $count));

        return self::SUCCESS;
    }
}
