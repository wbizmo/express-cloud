<?php

declare(strict_types=1);

return [
    'currency' => env('EXPRESS_CLOUD_CURRENCY', 'NGN'),
    'codes' => [
        'cash' => '1000',
        'bank' => '1010',
        'card_clearing' => '1020',
        'accounts_receivable' => '1100',
        'prepayments' => '1150',
        'inventory' => '1200',
        'fixed_assets' => '1300',
        'accumulated_depreciation' => '1390',
        'accounts_payable' => '2000',
        'landed_cost_clearing' => '2050',
        'accrued_expenses' => '2060',
        'input_tax' => '2110',
        'output_tax' => '2100',
        'customer_deposits' => '2200',
        'fixed_asset_clearing' => '2300',
        'owner_equity' => '3000',
        'retained_earnings' => '3100',
        'sales_revenue' => '4000',
        'sales_returns' => '4010',
        'cost_of_goods_sold' => '5000',
        'purchase_returns' => '5010',
        'inventory_variance_gain' => '4020',
        'asset_disposal_gain' => '4030',
        'inventory_variance_loss' => '5020',
        'cash_over_short' => '5030',
        'depreciation_expense' => '6000',
        'general_expense' => '6100',
        'bank_charges' => '6110',
        'asset_disposal_loss' => '6120',
        'opening_balance' => '9990',
    ],
];
