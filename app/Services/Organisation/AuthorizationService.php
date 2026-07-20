<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Permission;
use Illuminate\Support\Collection;

final class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $permissionCache = [];

    public function hasPermission(Account $account, string $permission): bool
    {
        $accountId = (string) $account->getKey();
        if (array_key_exists($permission, $this->permissionCache[$accountId] ?? [])) {
            return $this->permissionCache[$accountId][$permission];
        }

        if ($this->isSystemOwner($account)) {
            return $this->permissionCache[$accountId][$permission] = true;
        }

        $allowed = $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', static fn ($query) => $query->where('permissions.slug', $permission))
            ->exists();

        return $this->permissionCache[$accountId][$permission] = $allowed;
    }

    /**
     * The system-owner role is meant to always have every permission in
     * the system, independent of whatever has or hasn't been explicitly
     * assigned to it in role_permissions — new permission slugs added
     * later should not silently lock this role out until someone
     * remembers to re-grant them.
     */
    private function isSystemOwner(Account $account): bool
    {
        return $account->roles()
            ->where('roles.is_active', true)
            ->where('roles.slug', 'system-owner')
            ->exists();
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
        if ($this->isSystemOwner($account)) {
            return Permission::query()->pluck('slug');
        }

        return $account->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(static fn ($role) => $role->permissions->pluck('slug'))
            ->unique()->values();
    }
}
