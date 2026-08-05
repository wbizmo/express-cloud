<?php

declare(strict_types=1);

return [
    'cron_secret' => env('OPERATIONS_CRON_SECRET', ''),
    'transaction_attempts' => (int) env('TRANSACTION_RETRY_ATTEMPTS', 5),
    'retry_base_delay_milliseconds' => (int) env(
        'TRANSACTION_RETRY_BASE_DELAY_MS',
        20,
    ),
    'lease_seconds' => (int) env('OPERATION_LEASE_SECONDS', 120),
];
