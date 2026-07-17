<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class PruneBackups extends Command
{
    protected $signature = 'backup:prune';

    protected $description = 'Delete expired backup archives and records.';

    public function handle(): int
    {
        $cutoff = now()->subDays(
            max(1, (int) config('backups.retention_days')),
        );

        $runs = BackupRun::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at')
            ->get();

        foreach ($runs as $run) {
            if ($run->path !== null) {
                Storage::disk($run->disk)->delete($run->path);
            }

            $run->delete();
        }

        $this->info(
            sprintf('Pruned %d backup records.', $runs->count()),
        );

        return self::SUCCESS;
    }
}
