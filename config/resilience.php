<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('PWA_ENABLED', true),
    'cache_version' => env('PWA_CACHE_VERSION', 'phase-10-v1'),
    'outbox_database' => env('PWA_OUTBOX_DATABASE', 'express-cloud-outbox'),
    'outbox_store' => env('PWA_OUTBOX_STORE', 'requests'),
    'operation_poll_seconds' => (int) env('PWA_OPERATION_POLL_SECONDS', 3),
    'operation_poll_attempts' => (int) env('PWA_OPERATION_POLL_ATTEMPTS', 20),
    'draft_max_age_hours' => (int) env('PWA_DRAFT_MAX_AGE_HOURS', 72),
];
