<?php

declare(strict_types=1);

return [
    'transaction_attempts' => (int) env('TRANSACTION_RETRY_ATTEMPTS', 5),
    'retry_base_delay_milliseconds' => (int) env('TRANSACTION_RETRY_BASE_DELAY_MS', 20),
    'lease_seconds' => (int) env('OPERATION_LEASE_SECONDS', 120),
    'alert_recipients' => array_values(array_unique(array_filter([
        trim((string) env('OPERATIONS_ALERT_EMAIL_1', '')),
        trim((string) env('OPERATIONS_ALERT_EMAIL_2', '')),
        trim((string) env('OPERATIONS_ALERT_EMAIL_3', '')),
    ]))),
    'report_time' => env('OPERATIONS_REPORT_TIME', '23:00'),
    'report_timezone' => env('OPERATIONS_REPORT_TIMEZONE', 'Africa/Lagos'),
    'cron_hmac_key' => env('OPERATIONS_CRON_HMAC_KEY', ''),
    'cron_clock_skew_seconds' => (int) env('OPERATIONS_CRON_CLOCK_SKEW_SECONDS', 300),
    'delivery_attempts' => (int) env('OPERATIONS_DELIVERY_ATTEMPTS', 3),
];
