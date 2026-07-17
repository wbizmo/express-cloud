<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Mail\EndOfDayDigestMail;
use App\Models\AlertRecipient;
use App\Models\BusinessSetting;
use App\Models\EndOfDayDigest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final readonly class EndOfDayDigestSender
{
    public function __construct(
        private EndOfDayDigestCompiler $compiler,
    ) {}

    public function send(string $businessDate): EndOfDayDigest
    {
        $existing = EndOfDayDigest::query()
            ->whereDate('business_date', $businessDate)
            ->first();

        if (
            $existing instanceof EndOfDayDigest
            && $existing->status === 'sent'
        ) {
            return $existing;
        }

        $digest = DB::transaction(function () use (
            $businessDate,
        ): EndOfDayDigest {
            $record = EndOfDayDigest::query()
                ->whereDate('business_date', $businessDate)
                ->lockForUpdate()
                ->first();

            if ($record instanceof EndOfDayDigest) {
                return $record;
            }

            return EndOfDayDigest::query()->create([
                'business_date' => $businessDate,
                'status' => 'processing',
                'recipient_count' => 0,
                'started_at' => now(),
            ]);
        });

        if ($digest->status === 'sent') {
            return $digest;
        }

        $summary = $this->compiler->compile($businessDate);
        $recipients = AlertRecipient::query()
            ->where('is_active', true)
            ->orderBy('email')
            ->pluck('email')
            ->all();

        $settings = BusinessSetting::current();

        try {
            foreach ($recipients as $email) {
                Mail::to((string) $email)->send(
                    new EndOfDayDigestMail(
                        $summary,
                        $settings->business_name,
                    ),
                );
            }

            $digest->forceFill([
                'status' => 'sent',
                'recipient_count' => count($recipients),
                'summary' => $summary,
                'sent_at' => now(),
                'failure_message' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $digest->forceFill([
                'status' => 'failed',
                'recipient_count' => count($recipients),
                'summary' => $summary,
                'failure_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000,
                ),
            ])->save();

            throw $exception;
        }

        return $digest;
    }
}
