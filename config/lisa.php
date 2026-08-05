<?php

declare(strict_types=1);

return [
    'snapshot_ttl_seconds' => (int) env('LISA_SNAPSHOT_TTL_SECONDS', 300),
    'snapshot_stale_seconds' => (int) env('LISA_SNAPSHOT_STALE_SECONDS', 60),
    'metric_version' => env('LISA_METRIC_VERSION', 'v1'),
    'conversation_page_size' => (int) env('LISA_CONVERSATION_PAGE_SIZE', 20),
    'message_page_size' => (int) env('LISA_MESSAGE_PAGE_SIZE', 50),
    'max_question_characters' => (int) env('LISA_MAX_QUESTION_CHARACTERS', 5000),
];
