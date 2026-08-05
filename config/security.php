<?php

declare(strict_types=1);

return [
    'headers_enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
    'content_security_policy' => env(
        'CONTENT_SECURITY_POLICY',
        "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; manifest-src 'self'; worker-src 'self' blob:",
    ),
    'permissions_policy' => env(
        'PERMISSIONS_POLICY',
        'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
    ),
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),
];
