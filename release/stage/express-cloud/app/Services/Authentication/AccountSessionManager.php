<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\Account;
use App\Models\AccountSession;
use Illuminate\Http\Request;

final class AccountSessionManager
{
    public function record(Request $request, Account $account): void
    {
        AccountSession::query()->updateOrCreate(
            ['session_identifier' => $request->session()->getId()],
            [
                'account_id' => $account->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function touch(Request $request): void
    {
        AccountSession::query()
            ->where('session_identifier', $request->session()->getId())
            ->whereNull('revoked_at')
            ->update(['last_activity_at' => now()]);
    }

    public function revokeCurrent(Request $request): void
    {
        AccountSession::query()
            ->where('session_identifier', $request->session()->getId())
            ->update(['revoked_at' => now()]);
    }
}
