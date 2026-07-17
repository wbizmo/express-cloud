<?php

declare(strict_types=1);

return [
    'version' => env('APP_VERSION', '1.0.0-dev'),

    'currency' => [
        'code' => 'NGN',
        'symbol' => '₦',
        'minor_unit_scale' => 100,
    ],

    'http' => [
        'force_https' => (bool) env('FORCE_HTTPS', false),
    ],

    'security' => [
        'data_encryption_key' => env('DATA_ENCRYPTION_KEY'),
        'blind_index_key' => env('BLIND_INDEX_KEY'),
        'data_encryption_version' => (int) env('DATA_ENCRYPTION_VERSION', 1),
    ],

    'infrastructure' => [
        'target_database' => 'mysql',
        'redis_optional' => true,
    ],
];
