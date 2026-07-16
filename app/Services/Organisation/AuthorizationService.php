<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Collection;

final class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $permissionCache = [];

    public function hasPermission(Account $account, string $permission): bool
    {
        $accountId = (string) $account->getKey();

        if (isset($this->permissionCache[$accountId][$permission])) {
            return $this->permissionCache[$accountId][$permission];
        }

        $allowed = $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', static function ($query) use ($permission): void {
                $query->where('permissions.slug', $permission);
            })
            ->exists();

        return $this->permissionCache[$accountId][$permission] = $allowed;
    }

    /** @param list<string> $permissions */
    public function hasAnyPermission(Account $account, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($account, $permission)) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, string> */
    public function permissionSlugs(Account $account): Collection
    {
        return $account->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(static fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();
    }

    public function canAccessBranch(Account $account, Branch|string $branch): bool
    {
        if ($account->is_allowed_all_branches) {
            return true;
        }

        $branchId = $branch instanceof Branch
            ? (string) $branch->getKey()
            : $branch;

        return $account->branches()->whereKey($branchId)->exists();
    }
}
