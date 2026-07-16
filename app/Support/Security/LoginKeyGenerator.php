<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

final class LoginKeyGenerator
{
    /**
     * Alphabet-only access keys.
     *
     * Ambiguous letters I, L and O are excluded.
     */
    public const string ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    public const int RAW_LENGTH = 8;

    public function generate(): string
    {
        $characters = [];

        for ($index = 0; $index < self::RAW_LENGTH; $index++) {
            $characters[] = self::ALPHABET[
                random_int(0, strlen(self::ALPHABET) - 1)
            ];
        }

        return self::format(implode('', $characters));
    }

    public static function normalize(string $value): string
    {
        $normalized = mb_strtoupper(trim($value));
        $normalized = str_replace(['-', ' '], '', $normalized);

        if (
            strlen($normalized) !== self::RAW_LENGTH
            || strspn($normalized, self::ALPHABET) !== self::RAW_LENGTH
        ) {
            throw new InvalidArgumentException(
                'Access key must contain exactly eight approved letters.',
            );
        }

        return $normalized;
    }

    public static function format(string $value): string
    {
        $normalized = self::normalize($value);

        return substr($normalized, 0, 4)
            .'-'
            .substr($normalized, 4, 4);
    }
}
