<?php

declare(strict_types=1);

return [
    'receipt_format' => env('POS_RECEIPT_FORMAT', '80mm'),
    'allowed_receipt_formats' => ['58mm', '80mm', 'a4'],
    'reprint_requires_approval_after' => (int) env('POS_REPRINT_APPROVAL_AFTER', 1),
    'variance_approval_threshold_kobo' => (int) env('POS_VARIANCE_APPROVAL_THRESHOLD_KOBO', 1000),
    'held_sale_expiry_hours' => (int) env('POS_HELD_SALE_EXPIRY_HOURS', 24),
];
