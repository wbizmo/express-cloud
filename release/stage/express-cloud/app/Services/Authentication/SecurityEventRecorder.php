<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Enums\Authentication\SecurityEventType;
use App\Models\Account;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;

final class SecurityEventRecorder
{
    /**
     * @param  array<string, bool|float|int|string|null>  $context
     */
    public function record(
        SecurityEventType $eventType,
        Request $request,
        ?Account $actor = null,
        ?Account $subject = null,
        array $context = [],
    ): void {
        SecurityEvent::query()->create([
            'event_type' => $eventType,
            'actor_account_id' => $actor?->getKey(),
            'subject_account_id' => $subject?->getKey(),
            'session_identifier' => $request->hasSession()
                ? $request->session()->getId()
                : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
