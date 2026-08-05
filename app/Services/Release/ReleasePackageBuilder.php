<?php

declare(strict_types=1);

namespace App\Services\Release;

use App\Models\ReleaseVerificationRun;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;

final readonly class ReleasePackageBuilder
{
    public function __construct(private Filesystem $files) {}

    public function build(ReleaseVerificationRun $run): ReleaseVerificationRun
    {
        if ($run->status !== 'passed') {
            throw new RuntimeException('A release package requires a passed verification run.');
        }
        $directory = base_path((string) config('release.artifact_directory', 'release'));
        $this->files->ensureDirectoryExists($directory);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $run->release_name) ?: 'express-cloud';
        $path = $directory.'/'.$name.'-'.$run->getKey().'.zip';
        $process = new Process(['git', 'archive', '--format=zip', '--output='.$path, 'HEAD'], base_path());
        $process->setTimeout(300);
        $process->mustRun();
        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('The release ZIP archive was not created.');
        }
        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new RuntimeException('Unable to hash the release ZIP archive.');
        }
        $run->forceFill(['artifact_path' => $path, 'artifact_sha256' => $hash])->save();

        return $run;
    }
}
