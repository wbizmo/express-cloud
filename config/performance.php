<?php

declare(strict_types=1);

return [
    'default_page_size' => (int) env('EXPRESS_CLOUD_PAGE_SIZE', 10),
    'cursor_page_size' => (int) env('EXPRESS_CLOUD_CURSOR_PAGE_SIZE', 50),
    'reference_cache_seconds' => (int) env('REFERENCE_CACHE_SECONDS', 300),
    'dashboard_cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 60),
    'stream_chunk_size' => (int) env('EXPORT_STREAM_CHUNK_SIZE', 500),
    'query_budget' => (int) env('HIGH_VOLUME_QUERY_BUDGET', 20),
];
