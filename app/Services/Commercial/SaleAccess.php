<?php

declare(strict_types=1);

namespace App\Services\Commercial;

use App\Models\Account;
use App\Models\Sale;
use App\Services\Organisation\AuthorizationService;
use App\Services\Organisation\BranchAccess;

final readonly class SaleAccess
{
    public function __construct(
        private AuthorizationService $authorization,
        private BranchAccess $branches,
    ) {}

    public function canView(Account $actor, Sale $sale): bool
    {
        if (! $this->branches->canAccessModel($actor, $sale)) {
            return false;
        }

        if ($this->authorization->hasPermission($actor, 'sales.view.all')) {
            return true;
        }

        if (! $this->authorization->hasAnyPermission(
            $actor,
            ['sales.view', 'sales.view.own'],
        )) {
            return false;
        }

        return (string) $sale->sold_by_account_id
            === (string) $actor->getKey();
    }
}
