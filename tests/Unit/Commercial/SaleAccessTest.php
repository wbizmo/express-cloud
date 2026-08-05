<?php

declare(strict_types=1);

namespace Tests\Unit\Commercial;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Services\Commercial\SaleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SaleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_with_own_sale_permission_and_branch_access_can_view_sale(): void
    {
        $account = $this->account();
        $branch = $this->branch();
        $account->branches()->attach($branch);

        $role = Role::query()->create([
            'name' => 'Cashier test',
            'slug' => 'cashier-test',
            'is_system' => false,
            'is_active' => true,
        ]);
        $permission = Permission::query()->create([
            'name' => 'View own sales',
            'slug' => 'sales.view.own',
            'group' => 'Commercial',
        ]);
        $role->permissions()->attach($permission);
        $account->roles()->attach($role);

        $sale = new Sale;
        $sale->branch_id = $branch->getKey();
        $sale->sold_by_account_id = $account->getKey();

        self::assertTrue(app(SaleAccess::class)->canView($account, $sale));
    }

    private function account(): Account
    {
        return Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Sale',
            'last_name' => 'Viewer',
            'login_key_encrypted' => 'ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => false,
        ]);
    }

    private function branch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Sale Access Branch',
            'code' => 'SALE-ACCESS',
            'address' => 'Test address',
            'status' => 'active',
            'is_head_office' => false,
        ]);
    }
}
