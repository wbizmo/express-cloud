<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Authentication\AccountStatus;
use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Account|null $account */
        /** @var Account|null $account */
        /** @var Account|null $account */
        /** @var Account|null $account */
        $account = $request->user();

        if (
            $account === null
            || $account->status !== AccountStatus::Active
        ) {
            auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('auth_error', 'Your account is unavailable.');
        }

        return $next($request);
    }
}
