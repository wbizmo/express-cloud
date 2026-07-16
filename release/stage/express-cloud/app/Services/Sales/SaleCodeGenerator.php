<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\Sales\SaleType;
use App\Models\Sale;
use Illuminate\Support\Str;

final class SaleCodeGenerator
{
    public function generate(SaleType $type): string
    {
        do {
            $code = $type->codePrefix()
                .'-'.now()->format('ymd')
                .'-'.Str::upper(Str::random(6));
        } while (Sale::query()->where('sale_code', $code)->exists());

        return $code;
    }
}
