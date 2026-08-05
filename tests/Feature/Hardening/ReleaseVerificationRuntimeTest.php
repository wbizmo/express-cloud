<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use App\Models\DataBackfillRun;
use App\Models\ReleaseVerificationRun;
use App\Services\Release\DataBackfillService;
use App\Services\Release\ProductionVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReleaseVerificationRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_verification_and_backfill_are_recorded(): void
    {
        config()->set('release.required_extensions', []);
        config()->set('operations.cron_hmac_key', 'test-key');
        config()->set('operations.alert_recipients', ['ops@example.com']);
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $verification = app(ProductionVerifier::class)->record('phase-13-runtime', str_repeat('a', 40));
        $backfill = app(DataBackfillService::class)->run('runtime', 50);

        self::assertSame('passed', $verification->status);
        self::assertSame('completed', $backfill->status);
        self::assertSame(1, ReleaseVerificationRun::query()->count());
        self::assertSame(1, DataBackfillRun::query()->count());
    }
}
