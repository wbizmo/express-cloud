<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CustomerGroup;
use App\Models\Department;
use App\Models\JobRole;
use App\Models\PaymentMethod;
use App\Models\PosTerminal;
use Illuminate\Database\Seeder;

final class SalesPosHrPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        CustomerGroup::query()->updateOrCreate(
            ['code' => 'RETAIL'],
            [
                'name' => 'Retail Customers',
                'default_payment_terms_days' => 0,
                'default_credit_limit_kobo' => 0,
                'price_group' => 'retail',
                'is_active' => true,
            ],
        );
        CustomerGroup::query()->updateOrCreate(
            ['code' => 'WHOLESALE'],
            [
                'name' => 'Wholesale Customers',
                'default_payment_terms_days' => 30,
                'default_credit_limit_kobo' => 0,
                'price_group' => 'wholesale',
                'is_active' => true,
            ],
        );

        PaymentMethod::query()->where('is_default_for_pos', true)->update([
            'method_type' => 'cash',
            'is_visible_in_pos' => true,
        ]);

        $branch = Branch::query()->where('status', 'active')
            ->orderByDesc('is_head_office')->orderBy('id')->first();
        if (! $branch instanceof Branch) {
            return;
        }

        $department = Department::query()->updateOrCreate(
            ['code' => 'OPERATIONS'],
            ['name' => 'Operations', 'branch_id' => $branch->getKey(), 'status' => 'active'],
        );
        JobRole::query()->updateOrCreate(
            ['code' => 'CASHIER'],
            [
                'title' => 'Cashier',
                'department_id' => $department->getKey(),
                'description' => 'POS checkout and cashier-shift responsibilities.',
                'status' => 'active',
            ],
        );
        PosTerminal::query()->updateOrCreate(
            ['code' => 'POS-DEFAULT'],
            [
                'branch_id' => $branch->getKey(),
                'name' => 'Default POS Terminal',
                'printer_profile' => (string) config('pos.receipt_format', '80mm'),
                'status' => 'active',
            ],
        );
    }
}
