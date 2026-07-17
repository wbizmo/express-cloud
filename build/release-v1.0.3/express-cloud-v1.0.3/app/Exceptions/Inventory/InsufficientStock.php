<?php

declare(strict_types=1);

namespace App\Exceptions\Inventory;

use DomainException;

final class InsufficientStock extends DomainException
{
    public static function forBranch(
        string $productName,
        string $branchName,
        string $available,
        string $requested,
    ): self {
        return new self(sprintf(
            'Not enough stock for %s at %s — %s available, %s requested.',
            $productName,
            $branchName,
            $available,
            $requested,
        ));
    }
}
