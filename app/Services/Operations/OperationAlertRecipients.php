<?php

declare(strict_types=1);

namespace App\Services\Operations;

final class OperationAlertRecipients
{
    /** @return list<string> */
    public function all(): array
    {
        $recipients = config('operations.alert_recipients', []);
        if (! is_array($recipients)) {
            return [];
        }

        return array_values(array_slice(array_unique(array_filter(array_map(
            static fn (mixed $email): string => filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL) ? trim((string) $email) : '',
            $recipients,
        ))), 0, 3));
    }
}
