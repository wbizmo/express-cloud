<?php

declare(strict_types=1);

namespace App\Services\SupplierFinance;

use App\Models\SupplierBill;
use Illuminate\Support\Str;

final class SupplierBillNumberGenerator
{
    public function generate(): string
    {
        do {
            $number = 'BILL-'
                .now()->format('ymd')
                .'-'.Str::upper(Str::random(6));
        } while (
            SupplierBill::query()
                ->where('bill_number', $number)
                ->exists()
        );

        return $number;
    }
}
