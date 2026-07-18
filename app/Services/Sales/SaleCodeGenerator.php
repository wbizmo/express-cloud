<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\Sales\SaleType;
use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Support\Str;

final class SaleCodeGenerator
{
    public function generate(SaleType $type, Branch $branch): string
    {
        $branchCode = Str::upper((string) Str::of($branch->code)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '')
            ->substr(0, 8));

        if ($branchCode === '') {
            $branchCode = 'BR';
        }

        do {
            $code = $branchCode
                .'-'.$type->codePrefix()
                .'-'.now()->format('ymd')
                .'-'.Str::upper(Str::random(6));
        } while (Sale::query()->where('sale_code', $code)->exists());

        return $code;
    }
}
