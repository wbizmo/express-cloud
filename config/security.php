<?php

declare(strict_types=1);

$defaultPolicy = "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; manifest-src 'self'; worker-src 'self' blob:";
$policy = (string) env('CONTENT_SECURITY_POLICY', $defaultPolicy);
$policy = str_replace(
    "style-src 'self' 'unsafe-inline';",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;",
    $policy,
);
$policy = str_replace(
    "font-src 'self' data:;",
    "font-src 'self' data: https://fonts.gstatic.com;",
    $policy,
);

return [
    'headers_enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
    'content_security_policy' => $policy,
    'permissions_policy' => env(
        'PERMISSIONS_POLICY',
        'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
    ),
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),
];
