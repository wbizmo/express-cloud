<?php

declare(strict_types=1);

return [
    'images' => [
        'disk' => env('PRODUCT_IMAGE_DISK', 'public'),
        'directory' => 'product-images',
        'maximum_kilobytes' => 4096,
    ],

    'pagination' => [
        'products' => 30,
        'suppliers' => 25,
        'classifications' => 50,
    ],

    'defaults' => [
        'minimum_stock' => 5,
        'tax_rate_percent' => 0,
    ],
];
