<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\Sale;
use App\Services\Commercial\SaleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureSaleVisibility
{
    public function __construct(private SaleAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sale = $request->route('sale') ?? $request->route('quote');
        $actor = $request->user();

        abort_unless(
            $sale instanceof Sale
            && $actor instanceof Account
            && $this->access->canView($actor, $sale),
            404,
        );

        return $next($request);
    }
}
