<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequirePermission
{
    public function __construct(
        private AuthorizationService $authorization,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        /** @var Account|null $account */
        $account = $request->user();

        abort_unless(
            $account !== null
            && $this->authorization->hasPermission($account, $permission),
            404,
        );

        return $next($request);
    }
}
