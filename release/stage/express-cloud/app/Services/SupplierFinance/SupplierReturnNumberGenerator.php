<?php

declare(strict_types=1);

namespace App\Services\SupplierFinance;

use App\Models\SupplierReturn;
use Illuminate\Support\Str;

final class SupplierReturnNumberGenerator
{
    public function generate(): string
    {
        do {
            $number = 'SRET-'
                .now()->format('ymd')
                .'-'.Str::upper(Str::random(6));
        } while (
            SupplierReturn::query()
                ->where('return_number', $number)
                ->exists()
        );

        return $number;
    }
}
