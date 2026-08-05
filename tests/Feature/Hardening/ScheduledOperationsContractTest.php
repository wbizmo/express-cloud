<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use App\Models\ExternalCronNonce;
use App\Services\Operations\HmacCronVerifier;
use App\Services\Operations\OperationAlertRecipients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ScheduledOperationsContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipients_are_deduplicated_and_limited_to_three(): void
    {
        config()->set('operations.alert_recipients', ['a@example.com', 'a@example.com', 'b@example.com', 'c@example.com', 'd@example.com']);
        self::assertSame(['a@example.com', 'b@example.com', 'c@example.com'], app(OperationAlertRecipients::class)->all());
    }

    public function test_hmac_post_is_accepted_once_and_replay_is_rejected(): void
    {
        config()->set('operations.cron_hmac_key', 'runtime-secret');
        config()->set('operations.cron_clock_skew_seconds', 300);
        $timestamp = time();
        $nonce = 'runtime-nonce';
        $body = '{"business_date":"2026-08-04"}';
        $canonical = implode("\n", ['POST', '/cron/operations/daily-close', (string) $timestamp, $nonce, hash('sha256', $body)]);
        $signature = hash_hmac('sha256', $canonical, 'runtime-secret');
        $request = Request::create('/cron/operations/daily-close', 'POST', [], [], [], [], $body);
        $request->headers->set('X-Express-Cloud-Timestamp', (string) $timestamp);
        $request->headers->set('X-Express-Cloud-Nonce', $nonce);
        $request->headers->set('X-Express-Cloud-Signature', $signature);
        app(HmacCronVerifier::class)->verify($request);
        self::assertSame(1, ExternalCronNonce::query()->count());

        $this->expectException(HttpException::class);
        app(HmacCronVerifier::class)->verify($request);
    }
}
