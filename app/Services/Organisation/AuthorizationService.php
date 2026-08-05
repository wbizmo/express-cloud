<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Permission;
use Illuminate\Support\Collection;

final class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $decisionCache = [];

    /** @var array<string, list<string>> */
    private array $slugCache = [];

    /** @var array<string, bool> */
    private array $systemOwnerCache = [];

    public function hasPermission(Account $account, string $permission): bool
    {
        $permission = trim($permission);

        if ($permission === '') {
            return false;
        }

        $accountId = (string) $account->getKey();

        if (array_key_exists($permission, $this->decisionCache[$accountId] ?? [])) {
            return $this->decisionCache[$accountId][$permission];
        }

        if ($this->isSystemOwner($account)) {
            return $this->decisionCache[$accountId][$permission] = true;
        }

        $allowed = false;

        foreach ($this->permissionSlugsArray($account) as $granted) {
            if ($granted === $permission || $this->wildcardMatches($granted, $permission)) {
                $allowed = true;
                break;
            }
        }

        return $this->decisionCache[$accountId][$permission] = $allowed;
    }

    public function isSystemOwner(Account $account): bool
    {
        $accountId = (string) $account->getKey();

        if (array_key_exists($accountId, $this->systemOwnerCache)) {
            return $this->systemOwnerCache[$accountId];
        }

        return $this->systemOwnerCache[$accountId] = $account->roles()
            ->where('roles.is_active', true)
            ->where('roles.slug', 'system-owner')
            ->exists();
    }

    /** @param list<string> $permissions */
    public function hasAnyPermission(Account $account, array $permissions): bool
    {
        foreach (array_unique($permissions) as $permission) {
            if ($this->hasPermission($account, $permission)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $permissions */
    public function hasAllPermissions(Account $account, array $permissions): bool
    {
        foreach (array_unique($permissions) as $permission) {
            if (! $this->hasPermission($account, $permission)) {
                return false;
            }
        }

        return true;
    }

    /** @return Collection<int, string> */
    public function permissionSlugs(Account $account): Collection
    {
        if ($this->isSystemOwner($account)) {
            return Permission::query()->orderBy('slug')->pluck('slug');
        }

        return collect($this->permissionSlugsArray($account));
    }

    public function forget(Account|string $account): void
    {
        $accountId = $account instanceof Account
            ? (string) $account->getKey()
            : $account;

        unset(
            $this->decisionCache[$accountId],
            $this->slugCache[$accountId],
            $this->systemOwnerCache[$accountId],
        );
    }

    /** @return list<string> */
    private function permissionSlugsArray(Account $account): array
    {
        $accountId = (string) $account->getKey();

        if (array_key_exists($accountId, $this->slugCache)) {
            return $this->slugCache[$accountId];
        }

        /** @var list<string> $slugs */
        $slugs = $account->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(static fn ($role) => $role->permissions->pluck('slug'))
            ->filter(static fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $this->slugCache[$accountId] = $slugs;
    }

    private function wildcardMatches(string $granted, string $requested): bool
    {
        if (! str_ends_with($granted, '*')) {
            return false;
        }

        return str_starts_with($requested, substr($granted, 0, -1));
    }
}
