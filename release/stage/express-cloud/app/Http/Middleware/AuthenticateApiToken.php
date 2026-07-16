<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $ability = null,
    ): Response {
        $plaintext = $request->bearerToken();

        if ($plaintext === null || $plaintext === '') {
            return response()->json([
                'error_code' => 'API_TOKEN_REQUIRED',
                'message' => 'A bearer token is required.',
            ], 401);
        }

        /** @var ApiToken|null $token */
        $token = ApiToken::query()
            ->where('token_hash', hash('sha256', $plaintext))
            ->first();

        if ($token === null || ! $token->active()) {
            return response()->json([
                'error_code' => 'API_TOKEN_INVALID',
                'message' => 'The bearer token is invalid or inactive.',
            ], 401);
        }

        if ($ability !== null && ! $token->allows($ability)) {
            return response()->json([
                'error_code' => 'API_ABILITY_DENIED',
                'message' => 'The token cannot perform this operation.',
            ], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
