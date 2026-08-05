<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PwaResilienceContractTest extends TestCase
{
    #[DataProvider('contracts')]
    public function test_phase_10_resilience_contracts(string $path, string $needle): void
    {
        $source = file_get_contents(base_path($path));
        self::assertIsString($source);
        self::assertStringContainsString($needle, $source);
    }

    /** @return list<array{string,string}> */
    public static function contracts(): array
    {
        return [
            ['public/manifest.webmanifest', '"display": "standalone"'],
            ['public/service-worker.js', "request.method !== 'GET'"],
            ['resources/js/resilience/operation-outbox.js', 'indexedDB.open'],
            ['resources/js/resilience/index.js', 'operationStatus'],
            ['resources/views/components/layout/app.blade.php', 'operation-recovery-template'],
            ['routes/admin.php', 'operations/recovery/{scope}/{idempotencyKey}'],
        ];
    }
}
