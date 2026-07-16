<?php

declare(strict_types=1);

namespace App\Services\Backup;

use PharData;

final class BackupArchive
{
    /**
     * @param  list<string>  $files
     */
    public function create(
        string $archivePath,
        array $files,
    ): void {
        $tarPath = preg_replace('/\.gz$/', '', $archivePath);

        if (! is_string($tarPath)) {
            throw new \RuntimeException(
                'Unable to derive backup archive path.',
            );
        }

        @unlink($tarPath);
        @unlink($archivePath);

        $archive = new PharData($tarPath);

        foreach ($files as $file) {
            if (! is_file($file)) {
                throw new \RuntimeException(
                    "Backup source file is missing: {$file}",
                );
            }

            $archive->addFile($file, basename($file));
        }

        $archive->compress(\Phar::GZ);
        unset($archive);
        @unlink($tarPath);

        if (! is_file($archivePath)) {
            throw new \RuntimeException(
                'Backup archive was not created.',
            );
        }
    }
}
