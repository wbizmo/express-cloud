<?php

declare(strict_types=1);

namespace App\Services\Api;

final class ApiTokenGenerator
{
    /**
     * @return array{
     *   plaintext:string,
     *   prefix:string,
     *   hash:string
     * }
     */
    public function generate(): array
    {
        $secret = rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '=',
        );
        $plaintext = 'ec_live_'.$secret;

        return [
            'plaintext' => $plaintext,
            'prefix' => mb_substr($plaintext, 0, 14),
            'hash' => hash('sha256', $plaintext),
        ];
    }
}
