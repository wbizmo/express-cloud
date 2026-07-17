<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, bool|float|int|string|null>  $context
     */
    public function record(
        Request $request,
        string $action,
        string $entityType,
        Model|string|null $entity = null,
        ?Branch $branch = null,
        ?array $before = null,
        ?array $after = null,
        array $context = [],
    ): void {
        /** @var Account|null $actor */
        $actor = $request->user();

        AuditLog::query()->create([
            'actor_account_id' => $actor?->getKey(),
            'actor_name' => $actor?->displayName(),
            'actor_role_snapshot' => $actor?->roles()
                ->pluck('name')
                ->implode(', '),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entity instanceof Model
                ? (string) $entity->getKey()
                : $entity,
            'branch_id' => $branch?->getKey(),
            'before_data' => $before,
            'after_data' => $after,
            'context' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
