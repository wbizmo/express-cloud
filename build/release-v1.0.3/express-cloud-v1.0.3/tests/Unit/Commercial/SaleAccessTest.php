<?php

declare(strict_types=1);

namespace Tests\Unit\Commercial;

use App\Models\Account;
use App\Models\Sale;
use App\Services\Commercial\SaleAccess;
use Tests\TestCase;

final class SaleAccessTest extends TestCase
{
    public function test_creator_can_view_own_sale(): void
    {
        $account = new Account;
        $account->setAttribute('id', '01ACCOUNT000000000000000001');

        $sale = new Sale;
        $sale->sold_by_account_id = '01ACCOUNT000000000000000001';

        self::assertTrue(
            (new SaleAccess)->canView($account, $sale),
        );
    }
}
