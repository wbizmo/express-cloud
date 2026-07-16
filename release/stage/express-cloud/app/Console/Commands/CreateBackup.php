<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\CreateApplicationBackup;
use Illuminate\Console\Command;

final class CreateBackup extends Command
{
    protected $signature = 'backup:create';

    protected $description = 'Create a checksummed application backup.';

    public function handle(
        CreateApplicationBackup $backups,
    ): int {
        $run = $backups->execute();

        $this->info(
            "Backup {$run->id} completed: {$run->path}",
        );

        return self::SUCCESS;
    }
}
