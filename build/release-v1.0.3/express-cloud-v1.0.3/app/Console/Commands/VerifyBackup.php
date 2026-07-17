<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\Backup\VerifyStoredBackup;
use Illuminate\Console\Command;

final class VerifyBackup extends Command
{
    protected $signature = 'backup:verify {backupRun}';

    protected $description = 'Verify the SHA-256 integrity of a stored backup.';

    public function handle(
        VerifyStoredBackup $verify,
    ): int {
        /** @var BackupRun $run */
        $run = BackupRun::query()->findOrFail(
            (string) $this->argument('backupRun'),
        );

        if (! $verify->execute($run)) {
            $this->error('Backup verification failed.');

            return self::FAILURE;
        }

        $this->info('Backup checksum verified.');

        return self::SUCCESS;
    }
}
