<?php

declare(strict_types=1);

namespace App\Services\Backup;

final class BackupManifest
{
    /**
     * @param list<array{
     *   name:string,
     *   path:string,
     *   size_bytes:int,
     *   checksum_sha256:string
     * }> $files
     * @return array<string, mixed>
     */
    public function make(array $files): array
    {
        return [
            'format_version' => 1,
            'application' => (string) config('app.name'),
            'environment' => (string) config('app.env'),
            'created_at' => now()->toIso8601String(),
            'database_connection' => (string) config(
                'database.default',
            ),
            'files' => $files,
        ];
    }
}
