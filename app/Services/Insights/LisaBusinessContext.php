<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;

final readonly class LisaBusinessContext
{
    public function __construct(private BusinessSnapshotService $snapshots) {}

    /** @return array<string,mixed> */
    public function for(Account $account, ?string $branchId = null): array
    {
        $snapshot = $this->snapshots->for($account, $branchId);

        return (array) ($snapshot['metrics'] ?? []);
    }
}
