<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Support\Money\Naira;

final class MoneyInput
{
    public function toKobo(string|int|float|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Naira::fromNaira((string) $value)->kobo;
    }
}
