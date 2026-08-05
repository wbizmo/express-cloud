<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Release\DataBackfillService;
use Illuminate\Console\Command;

final class BackfillHardeningData extends Command
{
    protected $signature = 'data:backfill-hardening {--source=current : Source label} {--chunk=500 : Chunk size}';

    protected $description = 'Run resumable integrity/backfill inspection for current and imported data.';

    public function handle(DataBackfillService $service): int
    {
        $run = $service->run((string) $this->option('source'), max(50, (int) $this->option('chunk')));
        $this->line(json_encode(['id' => (string) $run->getKey(), 'status' => $run->status, 'counts' => $run->counts], JSON_THROW_ON_ERROR));

        return $run->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
