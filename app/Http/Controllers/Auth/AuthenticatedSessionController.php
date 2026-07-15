<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Authentication\SecurityEventType;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Account;
use App\Services\Authentication\AccessKeyAuthenticator;
use App\Services\Authentication\AccountSessionManager;
use App\Services\Authentication\SecurityEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class AuthenticatedSessionController
{
    public function __construct(
        private AccessKeyAuthenticator $authenticator,
        private AccountSessionManager $sessionManager,
        private SecurityEventRecorder $securityEvents,
    ) {}

    public function store(LoginRequest $request): RedirectResponse
    {
        $account = $this->authenticator->findAuthenticatableAccount(
            $request->string('account_public_id')->toString(),
            $request->string('access_key')->toString(),
        );

        if ($account === null) {
            $this->securityEvents->record(
                SecurityEventType::LoginFailed,
                $request,
                context: ['reason' => 'invalid_credentials'],
            );

            return back()
                ->withInput($request->safe()->only('account_public_id'))
                ->withErrors([
                    'access_key' => 'The selected staff member or access key is incorrect.',
                ]);
        }

        Auth::login($account);
        $request->session()->regenerate();
        $request->session()->put('last_activity_timestamp', time());

        $account->forceFill([
            'last_authenticated_at' => now(),
        ])->save();

        $this->sessionManager->record($request, $account);

        $this->securityEvents->record(
            SecurityEventType::LoginSucceeded,
            $request,
            actor: $account,
            subject: $account,
        );

        return redirect()->intended(route('staff.dashboard'));
    }

    public function destroy(
        Request $request,
        AccountSessionManager $sessionManager,
        SecurityEventRecorder $securityEvents,
    ): RedirectResponse {
        /** @var Account|null $account */
        /** @var Account|null $account */
        /** @var Account|null $account */
        /** @var Account|null $account */
        $account = $request->user();

        if ($account !== null) {
            $securityEvents->record(
                SecurityEventType::Logout,
                $request,
                actor: $account,
                subject: $account,
            );
        }

        $sessionManager->revokeCurrent($request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'You have been signed out.');
    }
}
