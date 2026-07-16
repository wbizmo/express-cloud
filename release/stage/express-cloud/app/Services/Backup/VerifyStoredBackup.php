<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\BackupRun;
use Illuminate\Support\Facades\Storage;

final readonly class VerifyStoredBackup
{
    public function __construct(private BackupIntegrity $integrity) {}

    public function execute(BackupRun $run): bool
    {
        if (
            $run->status !== 'completed'
            || $run->path === null
            || $run->checksum_sha256 === null
        ) {
            return false;
        }

        $disk = Storage::disk($run->disk);

        if (! $disk->exists($run->path)) {
            return false;
        }

        $stream = $disk->readStream($run->path);

        if ($stream === false) {
            return false;
        }

        $temporary = tempnam(
            sys_get_temp_dir(),
            'express-cloud-backup-',
        );

        if ($temporary === false) {
            fclose($stream);

            return false;
        }

        $target = fopen($temporary, 'wb');

        if ($target === false) {
            fclose($stream);
            @unlink($temporary);

            return false;
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }

        try {
            return $this->integrity->verify(
                $temporary,
                $run->checksum_sha256,
            );
        } finally {
            @unlink($temporary);
        }
    }
}
