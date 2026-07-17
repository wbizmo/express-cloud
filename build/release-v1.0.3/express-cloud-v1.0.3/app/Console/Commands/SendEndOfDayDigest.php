<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Operations\EndOfDayDigestSender;
use Illuminate\Console\Command;

final class SendEndOfDayDigest extends Command
{
    protected $signature = 'operations:send-end-of-day-digest
        {--date= : Business date in YYYY-MM-DD format}';

    protected $description = 'Send the idempotent end-of-day operations digest.';

    public function handle(
        EndOfDayDigestSender $sender,
    ): int {
        $date = (string) (
            $this->option('date')
            ?: today()->toDateString()
        );

        $digest = $sender->send($date);

        $this->info(
            'Digest status: '.$digest->status
            .'; recipients: '.$digest->recipient_count,
        );

        return self::SUCCESS;
    }
}
