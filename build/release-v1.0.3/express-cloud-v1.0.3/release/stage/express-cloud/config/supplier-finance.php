<?php

declare(strict_types=1);

return [
    'attachments' => [
        'disk' => env('SUPPLIER_DOCUMENT_DISK', 'local'),
        'directory' => 'supplier-finance/attachments',
        'maximum_kilobytes' => 10_240,
        'extensions' => [
            'pdf',
            'jpg',
            'jpeg',
            'png',
            'webp',
            'xlsx',
            'docx',
        ],
    ],

    'pagination' => [
        'bills' => 40,
        'returns' => 40,
        'supplier_balances' => 50,
    ],
];
