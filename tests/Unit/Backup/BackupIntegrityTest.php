<?php

declare(strict_types=1);

namespace Tests\Unit\Backup;

use App\Services\Backup\BackupIntegrity;
use PHPUnit\Framework\TestCase;

final class BackupIntegrityTest extends TestCase
{
    public function test_checksum_verification_detects_changes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-test-');

        self::assertIsString($path);

        file_put_contents($path, 'original');

        $integrity = new BackupIntegrity;
        $checksum = $integrity->checksum($path);

        self::assertTrue(
            $integrity->verify($path, $checksum),
        );

        file_put_contents($path, 'changed');

        self::assertFalse(
            $integrity->verify($path, $checksum),
        );

        @unlink($path);
    }
}
