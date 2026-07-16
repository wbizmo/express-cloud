<?php

declare(strict_types=1);

return [
    'products' => [
        'disk' => env('IMPORT_DISK', 'local'),
        'directory' => 'imports/products',
        'error_directory' => 'imports/products/errors',
        'maximum_kilobytes' => 10_240,
        'preview_rows' => 50,
        'batch_size' => 250,
        'allowed_extensions' => ['xlsx'],
    ],
];
