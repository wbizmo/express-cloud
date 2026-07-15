<?php

declare(strict_types=1);

namespace App\DTOs\Authentication;

final readonly class LoginAttemptData
{
    public function __construct(
        public string $accountPublicId,
        public string $accessKey,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}
}
