<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;
use App\Models\BusinessSnapshot;
use App\Services\Organisation\AuthorizationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class BusinessSnapshotService
{
    public function __construct(
        private AuthorizationService $authorization,
        private BusinessSnapshotBuilder $builder,
    ) {}

    /** @return array<string,mixed> */
    public function for(Account $account, ?string $branchId = null, bool $fresh = false): array
    {
        [$branchIds, $branchHash] = $this->branchScope($account, $branchId);
        $permissionSlugs = $this->authorization->permissionSlugs($account)->values()->all();
        $permissionHash = hash('sha256', implode('|', $permissionSlugs));
        $periodEnd = CarbonImmutable::today((string) config('app.timezone'))->toDateString();
        $periodStart = CarbonImmutable::parse($periodEnd)->startOfMonth()->toDateString();
        $periodKey = $periodStart.'..'.$periodEnd;
        $version = (string) config('lisa.metric_version', 'v1');
        $cacheKey = hash('sha256', implode('|', ['default', $branchHash, $permissionHash, $periodKey, $version]));

        /** @var BusinessSnapshot|null $snapshot */
        $snapshot = BusinessSnapshot::query()->where('cache_key', $cacheKey)->first();
        if (! $fresh && $snapshot instanceof BusinessSnapshot) {
            $expiresAt = $snapshot->expires_at === null ? null : CarbonImmutable::parse($snapshot->expires_at);
            $staleAt = $snapshot->stale_at === null ? null : CarbonImmutable::parse($snapshot->stale_at);
            if ($expiresAt?->isFuture()) {
                return $this->response($snapshot, $staleAt?->isPast() ?? false);
            }
        }

        $built = $this->builder->build($branchIds, $periodStart, $periodEnd);
        $evidenceHash = hash('sha256', json_encode($built['evidence'], JSON_THROW_ON_ERROR));
        $now = now();
        $snapshot = DB::transaction(function () use (
            $snapshot, $cacheKey, $branchHash, $permissionHash, $periodKey,
            $version, $built, $evidenceHash, $now,
        ): BusinessSnapshot {
            $record = $snapshot ?? new BusinessSnapshot;
            $record->forceFill([
                'cache_key' => $cacheKey,
                'company_key' => 'default',
                'branch_scope_hash' => $branchHash,
                'permission_scope_hash' => $permissionHash,
                'period_key' => $periodKey,
                'metric_version' => $version,
                'payload' => $built['payload'],
                'evidence_hash' => $evidenceHash,
                'generated_at' => $now,
                'stale_at' => $now->copy()->addSeconds(max(10, (int) config('lisa.snapshot_stale_seconds', 60))),
                'expires_at' => $now->copy()->addSeconds(max(30, (int) config('lisa.snapshot_ttl_seconds', 300))),
            ])->save();
            $record->evidence()->delete();
            $record->evidence()->createMany($built['evidence']);

            return $record->fresh('evidence') ?? $record;
        });

        return $this->response($snapshot, false);
    }

    public function invalidate(): int
    {
        return BusinessSnapshot::query()->where('expires_at', '>', now())->update(['expires_at' => now()]);
    }

    /** @return array{0:list<string>|null,1:string} */
    private function branchScope(Account $account, ?string $branchId): array
    {
        if ($branchId !== null && $branchId !== '') {
            $allowed = $account->is_allowed_all_branches
                || $account->branches()->whereKey($branchId)->exists();
            abort_unless($allowed, 404);

            return [[$branchId], hash('sha256', $branchId)];
        }
        if ($account->is_allowed_all_branches) {
            return [null, hash('sha256', '*')];
        }
        $ids = $account->branches()->orderBy('branches.id')->pluck('branches.id')->map(static fn ($id): string => (string) $id)->all();

        return [$ids, hash('sha256', implode('|', $ids))];
    }

    /** @return array<string,mixed> */
    private function response(BusinessSnapshot $snapshot, bool $stale): array
    {
        return [
            'snapshot_id' => (string) $snapshot->getKey(),
            'generated_at' => $snapshot->generated_at === null ? null : CarbonImmutable::parse($snapshot->generated_at)->toAtomString(),
            'stale' => $stale,
            'evidence_hash' => $snapshot->evidence_hash,
            'metrics' => $snapshot->payload,
        ];
    }
}
