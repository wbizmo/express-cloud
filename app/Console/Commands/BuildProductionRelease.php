<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReleaseVerificationRun;
use App\Services\Release\ReleasePackageBuilder;
use Illuminate\Console\Command;

final class BuildProductionRelease extends Command
{
    protected $signature = 'release:package {verification : Verification run ULID}';

    protected $description = 'Build a sanitized production ZIP from a passed verification run.';

    public function handle(ReleasePackageBuilder $builder): int
    {
        /** @var ReleaseVerificationRun $run */
        $run = ReleaseVerificationRun::query()->findOrFail((string) $this->argument('verification'));
        $run = $builder->build($run);
        $this->info('Artifact: '.$run->artifact_path);
        $this->info('SHA-256: '.$run->artifact_sha256);

        return self::SUCCESS;
    }
}
