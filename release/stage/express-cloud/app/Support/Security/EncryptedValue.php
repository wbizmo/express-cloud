<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

final readonly class EncryptedValue
{
    private Encrypter $encrypter;

    public function __construct(
        ?string $base64Key = null,
        public int $version = 1,
    ) {
        $key = $base64Key ?? (string) config(
            'express-cloud.security.data_encryption_key',
        );

        if ($key === '') {
            throw new InvalidArgumentException(
                'DATA_ENCRYPTION_KEY is not configured.',
            );
        }

        $decoded = str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true)
            : $key;

        if (! is_string($decoded) || strlen($decoded) !== 32) {
            throw new InvalidArgumentException(
                'DATA_ENCRYPTION_KEY must resolve to exactly 32 bytes.',
            );
        }

        $this->encrypter = new Encrypter($decoded, 'AES-256-CBC');
    }

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            throw new InvalidArgumentException(
                'Sensitive values cannot be empty.',
            );
        }

        return json_encode([
            'v' => $this->version,
            'ciphertext' => $this->encrypter->encryptString($plaintext),
        ], JSON_THROW_ON_ERROR);
    }

    public function decrypt(string $payload): string
    {
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! isset($decoded['ciphertext'])) {
            throw new DecryptException('Encrypted payload is malformed.');
        }

        return $this->encrypter->decryptString(
            (string) $decoded['ciphertext'],
        );
    }
}
