<?php

declare(strict_types=1);

namespace App\Services\Backup;

final class BackupIntegrity
{
    public function checksum(string $path): string
    {
        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum)) {
            throw new \RuntimeException(
                'Unable to calculate backup checksum.',
            );
        }

        return $checksum;
    }

    public function verify(
        string $path,
        string $expected,
    ): bool {
        return is_file($path)
            && hash_equals($expected, $this->checksum($path));
    }
}
