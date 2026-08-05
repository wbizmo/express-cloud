<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Services\Organisation\BranchAccess;
use Illuminate\Database\Eloquent\Model;

final readonly class BranchScopedResourcePolicy
{
    public function __construct(private BranchAccess $branches) {}

    public function view(Account $account, Model $resource): bool
    {
        return $this->branches->canAccessModel($account, $resource);
    }

    public function update(Account $account, Model $resource): bool
    {
        return $this->view($account, $resource);
    }

    public function delete(Account $account, Model $resource): bool
    {
        return $this->view($account, $resource);
    }

    public function restore(Account $account, Model $resource): bool
    {
        return $this->view($account, $resource);
    }

    public function forceDelete(Account $account, Model $resource): bool
    {
        return $this->view($account, $resource);
    }
}
