<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class BranchAccess
{
    /** @var array<string, list<string>> */
    private array $branchIdCache = [];

    public function canAccess(Account $account, Branch|string $branch): bool
    {
        if ($account->is_allowed_all_branches) {
            return true;
        }

        $branchId = $branch instanceof Branch
            ? (string) $branch->getKey()
            : trim($branch);

        return $branchId !== ''
            && in_array($branchId, $this->allowedBranchIds($account), true);
    }

    public function enforce(Account $account, Branch|string $branch): void
    {
        abort_unless($this->canAccess($account, $branch), 404);
    }

    public function canAccessAccount(Account $actor, Account $target): bool
    {
        if ((string) $actor->getKey() === (string) $target->getKey()) {
            return true;
        }

        if ($actor->is_allowed_all_branches) {
            return true;
        }

        $allowedIds = $this->allowedBranchIds($actor);

        if ($allowedIds === []) {
            return false;
        }

        return $target->branches()
            ->whereIn('branches.id', $allowedIds)
            ->exists();
    }

    public function canAccessModel(Account $account, Model $model): bool
    {
        if ($model instanceof Account) {
            return $this->canAccessAccount($account, $model);
        }

        if ($model instanceof Branch) {
            return $this->canAccess($account, $model);
        }

        foreach ($this->modelBranchIds($model) as $branchId) {
            if (! $this->canAccess($account, $branchId)) {
                return false;
            }
        }

        return true;
    }

    public function enforceModel(Account $account, Model $model): void
    {
        abort_unless($this->canAccessModel($account, $model), 404);
    }

    public function scope(Account $account, Builder $query, string $column = 'branch_id'): Builder
    {
        if ($account->is_allowed_all_branches) {
            return $query;
        }

        return $query->whereIn($column, $this->allowedBranchIds($account));
    }

    /** @return list<string> */
    public function allowedBranchIds(Account $account): array
    {
        $accountId = (string) $account->getKey();

        if (array_key_exists($accountId, $this->branchIdCache)) {
            return $this->branchIdCache[$accountId];
        }

        /** @var list<string> $ids */
        $ids = $account->branches()
            ->orderBy('branches.id')
            ->pluck('branches.id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        return $this->branchIdCache[$accountId] = $ids;
    }

    public function forget(Account|string $account): void
    {
        $accountId = $account instanceof Account
            ? (string) $account->getKey()
            : $account;

        unset($this->branchIdCache[$accountId]);
    }

    /** @return list<string> */
    private function modelBranchIds(Model $model): array
    {
        $attributes = $model->getAttributes();
        $ids = [];

        foreach ([
            'branch_id',
            'source_branch_id',
            'destination_branch_id',
            'from_branch_id',
            'to_branch_id',
        ] as $attribute) {
            $value = $attributes[$attribute] ?? null;

            if (is_string($value) && $value !== '') {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }
}
