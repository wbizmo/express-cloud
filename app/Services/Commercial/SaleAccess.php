<?php

declare(strict_types=1);

namespace App\Services\Commercial;

use App\Models\Account;
use App\Models\Sale;

final class SaleAccess
{
    public function canView(Account $actor, Sale $sale): bool
    {
        if ($actor->can('sales.view.all')) {
            return true;
        }

        return (string) $sale->sold_by_account_id
            === (string) $actor->getKey();
    }
}
