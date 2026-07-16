<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

final class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['1000', 'Cash on Hand', 'asset', true],
            ['1010', 'Bank Accounts', 'asset', true],
            ['1020', 'Card and POS Clearing', 'asset', true],
            ['1100', 'Accounts Receivable', 'asset', true],
            ['1200', 'Inventory Asset', 'asset', true],
            ['1300', 'Fixed Assets', 'asset', true],
            ['1390', 'Accumulated Depreciation', 'asset', true],
            ['2000', 'Accounts Payable', 'liability', true],
            ['2100', 'Output Tax Payable', 'liability', true],
            ['2200', 'Customer Deposits', 'liability', true],
            ['2300', 'Fixed Asset Clearing', 'liability', true],
            ['3000', 'Owner Equity', 'equity', true],
            ['4000', 'Sales Revenue', 'revenue', true],
            ['4010', 'Sales Returns and Allowances', 'revenue', true],
            ['5000', 'Cost of Goods Sold', 'expense', true],
            ['5010', 'Purchase Returns', 'expense', true],
            ['6000', 'Depreciation Expense', 'expense', true],
            ['6100', 'General Operating Expense', 'expense', false],
            ['9990', 'Opening Balance Clearing', 'equity', true],
        ];

        foreach ($accounts as [$code, $name, $type, $control]) {
            LedgerAccount::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'is_control_account' => $control,
                    'is_system' => true,
                    'is_active' => true,
                    'allow_manual_posting' => ! $control,
                ],
            );
        }
    }
}
