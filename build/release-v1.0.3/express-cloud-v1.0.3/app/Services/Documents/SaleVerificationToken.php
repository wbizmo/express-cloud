<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Sale;

final class SaleVerificationToken
{
    public function issue(Sale $sale): string
    {
        return hash_hmac(
            'sha256',
            (string) $sale->getKey().'|'.$sale->sale_code,
            (string) config('app.key'),
        );
    }

    public function valid(Sale $sale, string $token): bool
    {
        return hash_equals($this->issue($sale), $token);
    }
}
