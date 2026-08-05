<?php

declare(strict_types=1);

return [
    'rounding_mode' => env('SALES_ROUNDING_MODE', 'nearest'),
    'rounding_increment_kobo' => (int) env('SALES_ROUNDING_INCREMENT_KOBO', 1),
    'discount_approval_basis_points' => (int) env('SALES_DISCOUNT_APPROVAL_BPS', 1500),
    'price_override_approval_basis_points' => (int) env('SALES_PRICE_OVERRIDE_APPROVAL_BPS', 1000),
    'credit_sale_requires_customer' => true,
    'default_payment_terms_days' => (int) env('SALES_DEFAULT_PAYMENT_TERMS_DAYS', 0),
];
