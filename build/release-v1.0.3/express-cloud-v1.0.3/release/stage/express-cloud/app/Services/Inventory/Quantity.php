<?php

declare(strict_types=1);

namespace App\Services\Inventory;

final class Quantity
{
    public function toMilliunits(string|int|float $quantity): int
    {
        $normalized = trim((string) $quantity);

        if (! preg_match('/^-?\d+(?:\.\d{1,3})?$/', $normalized)) {
            throw new \InvalidArgumentException(
                'Quantity supports at most three decimal places.',
            );
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $decimal] = array_pad(
            explode('.', $unsigned, 2),
            2,
            '',
        );

        $milliunits = ((int) $whole * 1000)
            + (int) str_pad($decimal, 3, '0');

        return $negative ? -$milliunits : $milliunits;
    }

    public function format(int $milliunits): string
    {
        $negative = $milliunits < 0;
        $absolute = abs($milliunits);
        $whole = intdiv($absolute, 1000);
        $decimal = $absolute % 1000;
        $formatted = $whole.'.'.str_pad((string) $decimal, 3, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').rtrim(rtrim($formatted, '0'), '.');
    }
}
