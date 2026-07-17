<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

final readonly class BlindIndex
{
    public function __construct(private ?string $key = null) {}

    public function make(string $value): string
    {
        $normalized = self::normalize($value);
        $key = $this->key ?? (string) config(
            'express-cloud.security.blind_index_key',
        );

        if ($key === '') {
            throw new InvalidArgumentException(
                'BLIND_INDEX_KEY is not configured.',
            );
        }

        return hash_hmac('sha256', $normalized, $key);
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
