<?php

declare(strict_types=1);

namespace App\Exceptions\Operations;

use DomainException;

final class IdempotencyConflict extends DomainException
{
    public static function fingerprintMismatch(string $scope): self
    {
        return new self(
            "The idempotency key for {$scope} was already used with a different request payload.",
        );
    }
}
