<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BusinessSnapshot;
use App\Models\ExternalCronNonce;
use Illuminate\Console\Command;

final class PruneOperationsState extends Command
{
    protected $signature = 'operations:prune-state';

    protected $description = 'Prune expired PWA/Lisa/cron operational state.';

    public function handle(): int
    {
        $snapshots = BusinessSnapshot::query()->where('expires_at', '<', now()->subDay())->delete();
        $nonces = ExternalCronNonce::query()->where('expires_at', '<', now())->delete();
        $this->info("Pruned {$snapshots} snapshot(s) and {$nonces} cron nonce(s).");

        return self::SUCCESS;
    }
}
