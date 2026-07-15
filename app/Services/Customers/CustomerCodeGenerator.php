<?php

declare(strict_types=1);

namespace App\Services\Customers;

use App\Models\Customer;
use Illuminate\Support\Str;

final class CustomerCodeGenerator
{
    public function generate(): string
    {
        do {
            $code = 'CUS-'.Str::upper(Str::random(8));
        } while (
            Customer::query()
                ->where('customer_code', $code)
                ->exists()
        );

        return $code;
    }
}
