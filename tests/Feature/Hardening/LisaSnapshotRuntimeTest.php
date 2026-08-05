<?php

declare(strict_types=1);

namespace Tests\Feature\Hardening;

use App\Models\Account;
use App\Models\Branch;
use App\Models\BusinessSnapshot;
use App\Models\BusinessSnapshotEvidence;
use App\Services\Insights\BusinessSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class LisaSnapshotRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_is_scoped_cached_evidenced_and_invalidatable(): void
    {
        $account = Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Lisa',
            'last_name' => 'Runtime',
            'login_key_encrypted' => 'ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);
        Branch::query()->create([
            'name' => 'Lisa Branch', 'code' => 'LISA-01', 'address' => 'Test',
            'status' => 'active', 'is_head_office' => true,
        ]);
        $service = app(BusinessSnapshotService::class);
        $first = $service->for($account);
        $second = $service->for($account);

        self::assertSame($first['snapshot_id'], $second['snapshot_id']);
        self::assertArrayHasKey('today', $first['metrics']);
        self::assertSame(1, BusinessSnapshot::query()->count());
        self::assertSame(5, BusinessSnapshotEvidence::query()->count());
        self::assertSame(1, $service->invalidate());
    }
}
