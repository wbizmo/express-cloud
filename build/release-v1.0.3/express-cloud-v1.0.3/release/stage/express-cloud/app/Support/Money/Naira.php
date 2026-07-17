<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class Naira implements JsonSerializable, Stringable
{
    public const string CURRENCY = 'NGN';

    public const string SYMBOL = '₦';

    public const int MINOR_UNIT_SCALE = 100;

    public function __construct(public int $kobo) {}

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromKobo(int $kobo): self
    {
        return new self($kobo);
    }

    public static function fromNaira(string|int $naira): self
    {
        $normalized = trim((string) $naira);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException(
                'Money must be a valid NGN amount with at most two decimal places.',
            );
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        $kobo = ((int) $whole * self::MINOR_UNIT_SCALE) + (int) $fraction;

        return new self($negative ? -$kobo : $kobo);
    }

    public function add(self $other): self
    {
        return new self($this->kobo + $other->kobo);
    }

    public function subtract(self $other): self
    {
        return new self($this->kobo - $other->kobo);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self($this->kobo * $quantity);
    }

    public function format(): string
    {
        $absolute = abs($this->kobo);
        $whole = intdiv($absolute, self::MINOR_UNIT_SCALE);
        $fraction = $absolute % self::MINOR_UNIT_SCALE;
        $sign = $this->kobo < 0 ? '-' : '';

        if ($fraction === 0) {
            return $sign.self::SYMBOL.number_format($whole);
        }

        return sprintf(
            '%s%s%s.%02d',
            $sign,
            self::SYMBOL,
            number_format($whole),
            $fraction,
        );
    }

    /**
     * @return array{currency: string, amount_minor: int, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'currency' => self::CURRENCY,
            'amount_minor' => $this->kobo,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
