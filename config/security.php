<?php

declare(strict_types=1);

/*
 * Livewire 4's standard Alpine evaluator compiles x-data/x-show/x-on
 * expressions with Function declarations. That runtime requires
 * 'unsafe-eval'. Without it, every Alpine-driven control remains inert.
 *
 * Express Cloud retains the remaining restrictive directives. A future
 * migration to Livewire's CSP-safe build must first move unsupported complex
 * inline expressions into registered Alpine.data() components.
 */
$defaultPolicy = "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; manifest-src 'self'; worker-src 'self' blob:";
$policy = trim((string) env('CONTENT_SECURITY_POLICY', $defaultPolicy));

if (preg_match('/script-src\s+([^;]*);/i', $policy, $matches) === 1) {
    $sources = trim((string) $matches[1]);

    if (! str_contains($sources, "'unsafe-eval'")) {
        $policy = str_replace(
            (string) $matches[0],
            "script-src {$sources} 'unsafe-eval';",
            $policy,
        );
    }
} else {
    $policy = rtrim($policy, " ;\t\n\r\0\x0B")
        ."; script-src 'self' 'unsafe-inline' 'unsafe-eval';";
}

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
