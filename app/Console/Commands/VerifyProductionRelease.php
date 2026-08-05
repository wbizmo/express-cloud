<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Release\ProductionVerifier;
use Illuminate\Console\Command;

final class VerifyProductionRelease extends Command
{
    protected $signature = 'release:verify {--name=express-cloud-production} {--commit=} {--fail-on-warning}';

    protected $description = 'Record production-readiness checks for a release candidate.';

    public function handle(ProductionVerifier $verifier): int
    {
        $run = $verifier->record((string) $this->option('name'), $this->option('commit') !== null ? (string) $this->option('commit') : null);
        /** @var array<string, array{passed: bool, detail: string}> $checks */
        $checks = is_array($run->checks) ? $run->checks : [];
        foreach ($checks as $name => $check) {
            $this->line(sprintf('[%s] %s: %s', $check['passed'] ? 'PASS' : 'FAIL', $name, $check['detail']));
        }
        $this->info('Verification status: '.$run->status);

        return $run->status === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
