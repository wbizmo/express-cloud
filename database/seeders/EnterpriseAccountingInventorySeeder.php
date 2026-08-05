<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LedgerAccount;
use App\Models\TreasuryAccount;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class EnterpriseAccountingInventorySeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['1000', 'CASH', 'debit', 'current_asset', 'operating', false, null],
            ['1010', 'BANK', 'debit', 'current_asset', 'operating', false, null],
            ['1020', 'POS_CLEARING', 'debit', 'current_asset', 'operating', false, null],
            ['1100', 'RECEIVABLES', 'debit', 'current_asset', 'operating', true, null],
            ['1150', 'PREPAYMENTS', 'debit', 'current_asset', 'operating', false, null],
            ['1200', 'INVENTORY', 'debit', 'current_asset', 'operating', false, null],
            ['1300', 'FIXED_ASSETS', 'debit', 'non_current_asset', 'investing', false, null],
            ['1390', 'ACCUMULATED_DEPRECIATION', 'credit', 'non_current_asset', 'investing', false, null],
            ['2000', 'PAYABLES', 'credit', 'current_liability', 'operating', true, null],
            ['2050', 'LANDED_COST_CLEARING', 'credit', 'current_liability', 'operating', true, null],
            ['2060', 'ACCRUED_EXPENSES', 'credit', 'current_liability', 'operating', false, null],
            ['2100', 'OUTPUT_TAX', 'credit', 'current_liability', 'operating', false, 'output'],
            ['2110', 'INPUT_TAX', 'debit', 'current_asset', 'operating', false, 'input'],
            ['2200', 'CUSTOMER_DEPOSITS', 'credit', 'current_liability', 'operating', true, null],
            ['2300', 'FIXED_ASSET_CLEARING', 'credit', 'current_liability', 'investing', false, null],
            ['3000', 'OWNER_EQUITY', 'credit', 'equity', 'financing', false, null],
            ['3100', 'RETAINED_EARNINGS', 'credit', 'equity', 'financing', false, null],
            ['4000', 'SALES', 'credit', 'revenue', 'operating', false, null],
            ['4010', 'SALES_RETURNS', 'debit', 'revenue', 'operating', false, null],
            ['4020', 'INVENTORY_GAIN', 'credit', 'other_income', 'operating', false, null],
            ['4030', 'ASSET_DISPOSAL_GAIN', 'credit', 'other_income', 'investing', false, null],
            ['5000', 'COGS', 'debit', 'cost_of_sales', 'operating', false, null],
            ['5010', 'PURCHASE_RETURNS', 'credit', 'cost_of_sales', 'operating', false, null],
            ['5020', 'INVENTORY_LOSS', 'debit', 'operating_expense', 'operating', false, null],
            ['5030', 'CASH_OVER_SHORT', 'debit', 'operating_expense', 'operating', false, null],
            ['6000', 'DEPRECIATION', 'debit', 'operating_expense', 'operating', false, null],
            ['6100', 'GENERAL_EXPENSE', 'debit', 'operating_expense', 'operating', false, null],
            ['6110', 'BANK_CHARGES', 'debit', 'operating_expense', 'operating', false, null],
            ['6120', 'ASSET_DISPOSAL_LOSS', 'debit', 'other_expense', 'investing', false, null],
            ['9990', 'OPENING_BALANCE', 'credit', 'equity', 'financing', false, null],
        ];

        foreach ($accounts as [$code, $group, $normal, $section, $cashFlow, $subledger, $taxType]) {
            LedgerAccount::query()->where('code', $code)->update([
                'group_code' => $group,
                'normal_balance' => $normal,
                'report_section' => $section,
                'cash_flow_section' => $cashFlow,
                'requires_subledger' => $subledger,
                'tax_type' => $taxType,
            ]);
        }

        foreach ([
            ['1150', 'Prepayments', 'asset'],
            ['2050', 'Landed Cost Clearing', 'liability'],
            ['2060', 'Accrued Expenses', 'liability'],
            ['2110', 'Input Tax Recoverable', 'asset'],
            ['3100', 'Retained Earnings', 'equity'],
            ['4030', 'Gain on Asset Disposal', 'revenue'],
            ['5030', 'Cash Over and Short', 'expense'],
            ['6110', 'Bank Charges', 'expense'],
            ['6120', 'Loss on Asset Disposal', 'expense'],
        ] as [$code, $name, $type]) {
            LedgerAccount::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'group_code' => match ($code) {
                        '1150' => 'PREPAYMENTS',
                        '2050' => 'LANDED_COST_CLEARING',
                        '2060' => 'ACCRUED_EXPENSES',
                        '2110' => 'INPUT_TAX',
                        '3100' => 'RETAINED_EARNINGS',
                        '4030' => 'ASSET_DISPOSAL_GAIN',
                        '5030' => 'CASH_OVER_SHORT',
                        '6110' => 'BANK_CHARGES',
                        default => 'ASSET_DISPOSAL_LOSS',
                    },
                    'normal_balance' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit',
                    'report_section' => match ($type) {
                        'asset' => 'current_asset',
                        'liability' => 'current_liability',
                        'equity' => 'equity',
                        'revenue' => 'other_income',
                        default => 'operating_expense',
                    },
                    'cash_flow_section' => in_array($code, ['4030', '6120'], true)
                        ? 'investing'
                        : 'operating',
                    'requires_subledger' => $code === '2050',
                    'tax_type' => $code === '2110' ? 'input' : null,
                    'is_control_account' => true,
                    'is_system' => true,
                    'is_active' => true,
                    'allow_manual_posting' => false,
                ],
            );
        }

        UnitOfMeasure::query()->updateOrCreate(
            ['code' => 'EA'],
            [
                'name' => 'Each',
                'dimension' => 'quantity',
                'conversion_numerator' => 1,
                'conversion_denominator' => 1,
                'decimal_places' => 0,
                'is_active' => true,
            ],
        );

        Branch::query()->orderBy('id')->each(function (Branch $branch): void {
            $warehouse = Warehouse::query()->updateOrCreate(
                [
                    'branch_id' => $branch->getKey(),
                    'is_default' => true,
                ],
                [
                    'code' => 'WH-'.$branch->code,
                    'name' => $branch->name.' Main Warehouse',
                    'type' => 'standard',
                    'status' => 'active',
                    'address' => $branch->address,
                    'allows_sales' => true,
                    'allows_receipts' => true,
                ],
            );

            foreach ([
                ['1000', 'cash', 'Cash Counter'],
                ['1010', 'bank', 'Bank Book'],
                ['1020', 'pos_clearing', 'POS Clearing'],
            ] as [$code, $type, $name]) {
                $ledger = LedgerAccount::query()->where('code', $code)->first();
                if ($ledger === null) {
                    continue;
                }
                TreasuryAccount::query()->updateOrCreate(
                    [
                        'ledger_account_id' => $ledger->getKey(),
                        'branch_id' => $branch->getKey(),
                        'type' => $type,
                    ],
                    [
                        'name' => $branch->name.' '.$name,
                        'currency' => (string) config('accounting.currency', 'NGN'),
                        'is_active' => true,
                    ],
                );
            }

            if ($warehouse->code === '') {
                $warehouse->forceFill(['code' => 'WH-'.Str::upper(Str::random(8))])->save();
            }
        });
    }
}
