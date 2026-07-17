<?php

declare(strict_types=1);

namespace App\Support\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ApplicationHealth
{
    /**
     * @return array{
     *   status:string,
     *   checks:array<string, array{ok:bool, detail:string}>
     * }
     */
    public function check(): array
    {
        $checks = [];

        try {
            DB::select('SELECT 1');
            $checks['database'] = [
                'ok' => true,
                'detail' => 'Database connection is available.',
            ];
        } catch (\Throwable) {
            $checks['database'] = [
                'ok' => false,
                'detail' => 'Database connection failed.',
            ];
        }

        try {
            $disk = (string) config('backups.disk');
            $probe = trim(
                (string) config('backups.directory'),
                '/',
            ).'/.health-probe';
            Storage::disk($disk)->put($probe, now()->toIso8601String());
            Storage::disk($disk)->delete($probe);

            $checks['backup_storage'] = [
                'ok' => true,
                'detail' => 'Backup storage is writable.',
            ];
        } catch (\Throwable) {
            $checks['backup_storage'] = [
                'ok' => false,
                'detail' => 'Backup storage is not writable.',
            ];
        }

        $checks['application_key'] = [
            'ok' => (string) config('app.key') !== '',
            'detail' => (string) config('app.key') !== ''
                ? 'Application key is configured.'
                : 'Application key is missing.',
        ];

        $healthy = collect($checks)
            ->every(
                static fn (array $check): bool => $check['ok'],
            );

        return [
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
        ];
    }
}
