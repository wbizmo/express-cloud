<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Operations\HmacCronVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyOperationsCronHmac
{
    public function __construct(private HmacCronVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->verifier->verify($request);

        return $next($request);
    }
}
