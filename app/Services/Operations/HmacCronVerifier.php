<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\ExternalCronNonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HmacCronVerifier
{
    public function verify(Request $request): void
    {
        $key = (string) config('operations.cron_hmac_key', '');
        abort_if($key === '', 404);

        $timestamp = (int) ($request->header('X-Express-Cloud-Timestamp') ?? '0');
        $nonce = trim((string) $request->header('X-Express-Cloud-Nonce', ''));
        $signature = trim((string) $request->header('X-Express-Cloud-Signature', ''));
        $skew = max(30, (int) config('operations.cron_clock_skew_seconds', 300));
        abort_if($timestamp <= 0 || abs(time() - $timestamp) > $skew, 401);
        abort_if($nonce === '' || strlen($nonce) > 120 || $signature === '', 401);

        $bodyHash = hash('sha256', (string) $request->getContent());
        $canonical = implode("\n", [
            strtoupper($request->method()),
            '/'.$request->path(),
            (string) $timestamp,
            $nonce,
            $bodyHash,
        ]);
        $expected = hash_hmac('sha256', $canonical, $key);
        abort_unless(hash_equals($expected, $signature), 401);

        DB::transaction(function () use ($nonce, $timestamp, $signature, $skew): void {
            $inserted = ExternalCronNonce::query()->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'nonce' => $nonce,
                'timestamp' => $timestamp,
                'signature_hash' => hash('sha256', $signature),
                'used_at' => now(),
                'expires_at' => now()->addSeconds($skew * 2),
            ]);
            abort_unless($inserted === 1, 409);
        });
    }
}
