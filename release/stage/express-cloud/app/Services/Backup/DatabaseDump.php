<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Symfony\Component\Process\Process;

final class DatabaseDump
{
    public function create(string $destination): void
    {
        $connection = (string) config('database.default');
        /** @var array<string, mixed> $database */
        $database = config("database.connections.{$connection}", []);
        $driver = (string) ($database['driver'] ?? '');

        if ($driver === 'mysql') {
            $command = [
                (string) config('backups.mysql_dump_binary'),
                '--single-transaction',
                '--skip-lock-tables',
                '--routines',
                '--triggers',
                '--host='.(string) ($database['host'] ?? '127.0.0.1'),
                '--port='.(string) ($database['port'] ?? '3306'),
                '--user='.(string) ($database['username'] ?? ''),
                '--result-file='.$destination,
                (string) ($database['database'] ?? ''),
            ];

            $process = new Process(
                $command,
                env: [
                    'MYSQL_PWD' => (string) ($database['password'] ?? ''),
                ],
            );
        } elseif ($driver === 'pgsql') {
            $command = [
                (string) config('backups.pg_dump_binary'),
                '--format=custom',
                '--file='.$destination,
                '--host='.(string) ($database['host'] ?? '127.0.0.1'),
                '--port='.(string) ($database['port'] ?? '5432'),
                '--username='.(string) ($database['username'] ?? ''),
                (string) ($database['database'] ?? ''),
            ];

            $process = new Process(
                $command,
                env: [
                    'PGPASSWORD' => (string) ($database['password'] ?? ''),
                ],
            );
        } else {
            throw new \RuntimeException(
                "Unsupported backup database driver: {$driver}",
            );
        }

        $process->setTimeout(1800);
        $process->mustRun();

        if (! is_file($destination) || filesize($destination) === 0) {
            throw new \RuntimeException(
                'Database dump completed without producing a file.',
            );
        }
    }
}
