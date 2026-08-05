<?php

declare(strict_types=1);

namespace App\Services\Operations;

use BackedEnum;
use DateTimeInterface;
use JsonException;
use JsonSerializable;
use Stringable;

final class RequestFingerprint
{
    private const IGNORED_KEYS = [
        '_method',
        '_token',
        'idempotency_key',
    ];

    /** @param array<string, mixed> $payload */
    public function hash(array $payload): string
    {
        try {
            return hash(
                'sha256',
                json_encode(
                    $this->canonicalize($payload),
                    JSON_THROW_ON_ERROR
                        | JSON_PRESERVE_ZERO_FRACTION
                        | JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE,
                ),
            );
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException(
                'The operation payload cannot be fingerprinted.',
                previous: $exception,
            );
        }
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(
                    fn (mixed $item): mixed => $this->canonicalize($item),
                    $value,
                );
            }

            $normalized = [];

            foreach ($value as $key => $item) {
                $key = (string) $key;

                if (in_array($key, self::IGNORED_KEYS, true)) {
                    continue;
                }

                $normalized[$key] = $this->canonicalize($item);
            }

            ksort($normalized, SORT_STRING);

            return $normalized;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof JsonSerializable) {
            return $this->canonicalize($value->jsonSerialize());
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value) || is_resource($value)) {
            throw new \InvalidArgumentException(
                'Operation fingerprints support only deterministic scalar and array payloads.',
            );
        }

        return $value;
    }
}
