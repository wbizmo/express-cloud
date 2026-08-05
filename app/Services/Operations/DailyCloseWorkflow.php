<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Mail\EndOfDayDigestMail;
use App\Models\BusinessSetting;
use App\Models\DailyCloseRun;
use App\Models\NotificationDelivery;
use App\Services\Accounting\FinancialPostingReconciler;
use App\Services\Reports\DailyOperationsDigest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final readonly class DailyCloseWorkflow
{
    public function __construct(
        private EndOfDayDigestCompiler $compiler,
        private DailyOperationsDigest $reports,
        private FinancialPostingReconciler $accounting,
        private OperationAlertRecipients $recipients,
    ) {}

    public function run(string $businessDate): DailyCloseRun
    {
        $date = CarbonImmutable::parse($businessDate)->toDateString();
        $token = (string) Str::ulid();
        $run = DB::transaction(function () use ($date, $token): DailyCloseRun {
            DailyCloseRun::query()->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'business_date' => $date,
                'status' => 'pending',
                'attempt_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            /** @var DailyCloseRun $record */
            $record = DailyCloseRun::query()->whereDate('business_date', $date)->lockForUpdate()->firstOrFail();
            if ($record->status === 'completed') {
                return $record;
            }
            $startedAt = $record->started_at === null ? null : CarbonImmutable::parse($record->started_at);
            if ($record->status === 'running' && $startedAt?->greaterThan(now()->subMinutes(30))) {
                return $record;
            }
            $record->forceFill([
                'status' => 'running',
                'attempt_count' => $record->attempt_count + 1,
                'lock_token' => $token,
                'failure_step' => null,
                'failure_message' => null,
                'started_at' => now(),
            ])->save();

            return $record;
        });

        if ($run->status === 'completed' || $run->lock_token !== $token) {
            return $run;
        }

        $step = 'compile';
        try {
            $summary = $this->compiler->compile($date);
            $step = 'reports';
            $report = $this->reports->generate(CarbonImmutable::parse($date));
            $step = 'accounting';
            $this->accounting->repair();
            $accounting = $this->accounting->audit();
            $step = 'notifications';
            $recipients = $this->recipients->all();
            $settings = BusinessSetting::current();
            foreach ($recipients as $recipient) {
                /** @var NotificationDelivery $delivery */
                $delivery = NotificationDelivery::query()->firstOrCreate([
                    'daily_close_run_id' => $run->getKey(),
                    'recipient' => $recipient,
                    'notification_type' => 'daily-close',
                ], ['status' => 'pending', 'attempt_count' => 0]);
                if ($delivery->status === 'sent') {
                    continue;
                }
                try {
                    $delivery->increment('attempt_count');
                    Mail::to($recipient)->send(new EndOfDayDigestMail($summary, $settings->business_name));
                    $delivery->forceFill(['status' => 'sent', 'sent_at' => now(), 'last_error' => null])->save();
                } catch (Throwable $exception) {
                    $delivery->forceFill([
                        'status' => 'failed',
                        'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                    ])->save();
                    throw $exception;
                }
            }
            $run->forceFill([
                'status' => 'completed',
                'summary' => [
                    'operations' => $summary,
                    'report_files' => $report['files'],
                    'accounting' => $accounting,
                    'recipient_count' => count($recipients),
                ],
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => 'failed',
                'failure_step' => $step,
                'failure_message' => Str::limit($exception->getMessage(), 4000, ''),
                'completed_at' => now(),
            ])->save();
            throw $exception;
        }

        return $run->fresh('deliveries') ?? $run;
    }
}
