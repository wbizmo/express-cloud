<?php

declare(strict_types=1);

namespace App\Services\Release;

use App\Models\ReleaseVerificationRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ProductionVerifier
{
    /** @return array<string,array{passed:bool,detail:string}> */
    public function checks(): array
    {
        $checks = [];
        foreach ((array) config('release.required_extensions', []) as $extension) {
            $checks['extension:'.$extension] = [
                'passed' => extension_loaded((string) $extension),
                'detail' => extension_loaded((string) $extension) ? 'loaded' : 'missing',
            ];
        }
        foreach ([
            'operation_requests', 'financial_postings', 'warehouses', 'pos_shifts',
            'business_snapshots', 'daily_close_runs', 'release_verification_runs',
        ] as $table) {
            $checks['table:'.$table] = [
                'passed' => Schema::hasTable($table),
                'detail' => Schema::hasTable($table) ? 'present' : 'missing',
            ];
        }
        try {
            DB::select('SELECT 1 AS healthy');
            $checks['database'] = ['passed' => true, 'detail' => 'query succeeded'];
        } catch (Throwable $exception) {
            $checks['database'] = ['passed' => false, 'detail' => $exception->getMessage()];
        }
        $checks['app_debug'] = [
            'passed' => ! app()->isProduction() || ! (bool) config('app.debug'),
            'detail' => (bool) config('app.debug') ? 'enabled' : 'disabled',
        ];
        $checks['app_key'] = [
            'passed' => trim((string) config('app.key')) !== '',
            'detail' => trim((string) config('app.key')) !== '' ? 'configured' : 'missing',
        ];
        $checks['cron_hmac'] = [
            'passed' => ! app()->isProduction() || trim((string) config('operations.cron_hmac_key')) !== '',
            'detail' => trim((string) config('operations.cron_hmac_key')) !== '' ? 'configured' : 'missing',
        ];
        $checks['alert_recipients'] = [
            'passed' => count((array) config('operations.alert_recipients')) <= 3,
            'detail' => (string) count((array) config('operations.alert_recipients')),
        ];

        return $checks;
    }

    public function record(string $releaseName, ?string $commitSha = null): ReleaseVerificationRun
    {
        $checks = $this->checks();
        $passed = collect($checks)->every(static fn (array $check): bool => $check['passed']);

        return ReleaseVerificationRun::query()->create([
            'release_name' => $releaseName,
            'commit_sha' => $commitSha,
            'status' => $passed ? 'passed' : 'failed',
            'checks' => $checks,
            'started_at' => now(),
            'completed_at' => now(),
            'failure_message' => $passed ? null : 'One or more production verification checks failed.',
        ]);
    }
}
