<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\BackupRun;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class CreateApplicationBackup
{
    public function __construct(
        private DatabaseDump $database,
        private BackupArchive $archive,
        private BackupManifest $manifest,
        private BackupIntegrity $integrity,
    ) {}

    public function execute(
        ?string $requestedByAccountId = null,
    ): BackupRun {
        $disk = (string) config('backups.disk');
        $directory = trim(
            (string) config('backups.directory'),
            '/',
        );
        $stamp = now()->format('Ymd-His');
        $identifier = Str::lower(Str::random(8));
        $working = storage_path(
            "app/backup-working/{$stamp}-{$identifier}",
        );

        if (! mkdir($working, 0750, true) && ! is_dir($working)) {
            throw new \RuntimeException(
                'Unable to create backup working directory.',
            );
        }

        $run = BackupRun::query()->create([
            'backup_type' => 'application',
            'status' => 'running',
            'disk' => $disk,
            'requested_by_account_id' => $requestedByAccountId,
            'started_at' => now(),
        ]);

        try {
            $databasePath = $working.'/database.dump';
            $this->database->create($databasePath);

            $files = [[
                'name' => 'database.dump',
                'path' => $databasePath,
                'size_bytes' => (int) filesize($databasePath),
                'checksum_sha256' => $this->integrity->checksum(
                    $databasePath,
                ),
            ]];

            $manifestData = $this->manifest->make($files);
            $manifestPath = $working.'/manifest.json';

            file_put_contents(
                $manifestPath,
                json_encode(
                    $manifestData,
                    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
                ),
            );

            $archiveName = "express-cloud-{$stamp}-{$identifier}.tar.gz";
            $localArchive = $working.'/'.$archiveName;

            $this->archive->create(
                $localArchive,
                [$databasePath, $manifestPath],
            );

            $storagePath = "{$directory}/{$archiveName}";
            $stream = fopen($localArchive, 'rb');

            if ($stream === false) {
                throw new \RuntimeException(
                    'Unable to open backup archive for storage.',
                );
            }

            try {
                Storage::disk($disk)->put($storagePath, $stream);
            } finally {
                fclose($stream);
            }

            $run->forceFill([
                'status' => 'completed',
                'path' => $storagePath,
                'checksum_sha256' => $this->integrity->checksum(
                    $localArchive,
                ),
                'size_bytes' => (int) filesize($localArchive),
                'manifest' => $manifestData,
                'completed_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $run->forceFill([
                'status' => 'failed',
                'failure_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    4000,
                ),
                'completed_at' => now(),
            ])->save();

            throw $exception;
        } finally {
            $this->deleteDirectory($working);
        }

        return $run->refresh();
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
