<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! (bool) config('security.headers_enabled', true)) {
            return $response;
        }

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->set('Origin-Agent-Cluster', '?1');
        $headers->set(
            'Permissions-Policy',
            (string) config('security.permissions_policy'),
        );
        $headers->set(
            'Content-Security-Policy',
            (string) config('security.content_security_policy'),
        );

        if ($request->isSecure()) {
            $maxAge = max(0, (int) config('security.hsts_max_age', 31536000));
            $headers->set(
                'Strict-Transport-Security',
                "max-age={$maxAge}; includeSubDomains",
            );
        }

        if (
            $request->user() !== null
            && str_contains((string) $headers->get('Content-Type'), 'text/html')
        ) {
            $headers->set('Cache-Control', 'no-store, private');
            $headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
