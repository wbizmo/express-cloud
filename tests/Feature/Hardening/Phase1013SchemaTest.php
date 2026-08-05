<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class Phase1013SchemaTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('tables')]
    public function test_phase_10_13_tables_exist(string $table): void
    {
        self::assertTrue(Schema::hasTable($table));
    }

    /** @return list<array{string}> */
    public static function tables(): array
    {
        return array_map(static fn (string $table): array => [$table], [
            'business_snapshots', 'business_snapshot_evidence', 'daily_close_runs',
            'notification_deliveries', 'external_cron_nonces',
            'release_verification_runs', 'data_backfill_runs',
        ]);
    }
}
