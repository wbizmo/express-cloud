<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Models\BusinessSetting;
use App\Services\Operations\EndOfDayDigestSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CronDigestController
{
    public function __invoke(
        Request $request,
        string $secret,
        EndOfDayDigestSender $sender,
    ): JsonResponse {
        $configured = (string) config(
            'operations.cron_secret',
        );

        abort_if(
            $configured === ''
            || ! hash_equals($configured, $secret),
            404,
        );

        $settings = BusinessSetting::current();
        $currentTime = now()->format('H:i');
        $scheduledTime = mb_substr(
            (string) $settings->end_of_day_digest_time,
            0,
            5,
        );

        if (
            ! $request->boolean('force')
            && $currentTime !== $scheduledTime
        ) {
            return response()->json([
                'status' => 'not_due',
                'current_time' => $currentTime,
                'scheduled_time' => $scheduledTime,
            ]);
        }

        $digest = $sender->send(today()->toDateString());

        return response()->json([
            'status' => $digest->status,
            'business_date' => (string) $digest->business_date,
            'recipient_count' => $digest->recipient_count,
        ]);
    }
}
