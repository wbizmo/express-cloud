<?php

declare(strict_types=1);

namespace App\Services\Inventory;

final class InventoryIdentity
{
    public function balance(
        string $warehouseId,
        string $productId,
        ?string $variantId = null,
        ?string $batchId = null,
        string $condition = 'available',
    ): string {
        return $this->hash([
            $warehouseId,
            $productId,
            $variantId,
            $batchId,
            $condition,
        ]);
    }

    public function reservation(
        string $referenceType,
        string $referenceId,
        string $warehouseId,
        string $productId,
        ?string $variantId = null,
        ?string $batchId = null,
    ): string {
        return $this->hash([
            $referenceType,
            $referenceId,
            $warehouseId,
            $productId,
            $variantId,
            $batchId,
        ]);
    }

    public function reorder(
        string $warehouseId,
        string $productId,
        ?string $variantId = null,
    ): string {
        return $this->hash([$warehouseId, $productId, $variantId]);
    }

    /** @param list<string|null> $parts */
    private function hash(array $parts): string
    {
        return hash(
            'sha256',
            implode('|', array_map(
                static fn (?string $part): string => $part ?? '-',
                $parts,
            )),
        );
    }
}
