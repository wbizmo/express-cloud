<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

final class BranchAccess
{
    public function canAccess(Account $account, Branch|string $branch): bool
    {
        $branchId = $branch instanceof Branch ? (string) $branch->getKey() : $branch;
        return $account->is_allowed_all_branches
            || $account->branches()->whereKey($branchId)->exists();
    }

    public function enforce(Account $account, Branch|string $branch): void
    {
        abort_unless($this->canAccess($account, $branch), 404);
    }

    public function scope(Account $account, Builder $query, string $column = 'branch_id'): Builder
    {
        if ($account->is_allowed_all_branches) return $query;
        return $query->whereIn($column, $account->branches()->select('branches.id'));
    }
}
