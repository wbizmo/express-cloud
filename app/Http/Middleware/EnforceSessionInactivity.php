<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Authentication\AccountSessionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceSessionInactivity
{
    public function __construct(
        private AccountSessionManager $sessionManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $lastActivity = (int) $request->session()->get(
            'last_activity_timestamp',
            time(),
        );

        $inactivitySeconds = (int) config(
            'authentication.session.inactivity_minutes',
            30,
        ) * 60;

        if ((time() - $lastActivity) > $inactivitySeconds) {
            $this->sessionManager->revokeCurrent($request);

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('auth_error', 'Your session expired. Sign in again.');
        }

        $request->session()->put('last_activity_timestamp', time());
        $this->sessionManager->touch($request);

        return $next($request);
    }
}
