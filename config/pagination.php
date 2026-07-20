<?php

declare(strict_types=1);

return [
    // Standard list pages (products, sales, suppliers, accounting entries, etc).
    'default' => (int) env('EXPRESS_CLOUD_PAGE_SIZE', 10),

    // Denser/heavier rows (e.g. audit log detail rows, journal entry lines).
    'compact' => (int) env('EXPRESS_CLOUD_COMPACT_PAGE_SIZE', 5),
];
