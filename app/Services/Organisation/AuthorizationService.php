<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;

final class AuthorizationService
{
    public function hasPermission(Account $account, string $permission): bool
    {
        return $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($permission): void {
                $query->where('permissions.slug', $permission);
            })
            ->exists();
    }

    public function canAccessBranch(Account $account, Branch|string $branch): bool
    {
        if ($account->is_allowed_all_branches) {
            return true;
        }

        $branchId = $branch instanceof Branch
            ? (string) $branch->getKey()
            : $branch;

        return $account->branches()
            ->whereKey($branchId)
            ->exists();
    }
}
