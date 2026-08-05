<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementType;
use App\Exceptions\Inventory\InsufficientStock;
use App\Models\Account;
use App\Models\InventoryBatch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Models\WarehouseStockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class WarehouseStockLedger
{
    public function __construct(private Quantity $quantity) {}

    public function receive(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $quantityMilliunits,
        int $unitCostKobo,
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        string $condition = 'available',
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?OperationRequest $operation = null,
        int $sequence = 1,
        ?string $note = null,
    ): StockMovement {
        if ($quantityMilliunits <= 0 || $unitCostKobo < 0) {
            throw new \InvalidArgumentException('Receipt quantity must be positive and cost cannot be negative.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $actor, $quantityMilliunits, $unitCostKobo,
            $variant, $batch, $condition, $referenceType, $referenceId,
            $operation, $sequence, $note,
        ): StockMovement {
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, $condition);
            $incomingValue = (int) round(($quantityMilliunits / 1000) * $unitCostKobo);
            $newQuantity = $balance->quantity_milliunits + $quantityMilliunits;
            $newValue = $balance->inventory_value_kobo + $incomingValue;
            $average = $newQuantity === 0
                ? 0
                : (int) round($newValue / ($newQuantity / 1000));

            $balance->forceFill([
                'quantity_milliunits' => $newQuantity,
                'weighted_average_cost_kobo' => max(0, $average),
                'inventory_value_kobo' => max(0, $newValue),
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $this->syncBranchAggregate($warehouse, $product);

            return $this->movement(
                $product, $warehouse, $actor, StockMovementType::Purchase,
                $quantityMilliunits, $newQuantity, $unitCostKobo, $newValue,
                $variant, $batch, $condition, $referenceType, $referenceId,
                null, null, $note, $operation, $sequence,
            );
        }, 1);
    }

    public function issue(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $quantityMilliunits,
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        string $condition = 'available',
        ?StockReservation $reservation = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?OperationRequest $operation = null,
        int $sequence = 1,
        ?string $note = null,
    ): StockMovement {
        if ($quantityMilliunits <= 0) {
            throw new \InvalidArgumentException('Issue quantity must be positive.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $actor, $quantityMilliunits, $variant, $batch,
            $condition, $reservation, $referenceType, $referenceId,
            $operation, $sequence, $note,
        ): StockMovement {
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, $condition);
            $available = $balance->availableMilliunits();
            if ($reservation instanceof StockReservation && $reservation->status === 'active') {
                $available += $reservation->quantity_milliunits;
            }

            if ($quantityMilliunits > $available) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $warehouse->name,
                    $this->quantity->format($available),
                    $this->quantity->format($quantityMilliunits),
                );
            }

            $unitCost = $balance->weighted_average_cost_kobo;
            $valueDelta = (int) round(($quantityMilliunits / 1000) * $unitCost);
            $newQuantity = $balance->quantity_milliunits - $quantityMilliunits;
            $newValue = max(0, $balance->inventory_value_kobo - $valueDelta);
            $reserved = $balance->reserved_milliunits;
            if ($reservation instanceof StockReservation && $reservation->status === 'active') {
                $reserved = max(0, $reserved - $reservation->quantity_milliunits);
                $reservation->forceFill([
                    'status' => 'consumed',
                    'released_at' => now(),
                ])->save();
            }

            $balance->forceFill([
                'quantity_milliunits' => $newQuantity,
                'reserved_milliunits' => $reserved,
                'inventory_value_kobo' => $newValue,
                'weighted_average_cost_kobo' => $newQuantity > 0 ? $unitCost : 0,
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $this->syncBranchAggregate($warehouse, $product);

            return $this->movement(
                $product, $warehouse, $actor, StockMovementType::Sale,
                -$quantityMilliunits, $newQuantity, $unitCost, $newValue,
                $variant, $batch, $condition, $referenceType, $referenceId,
                null, null, $note, $operation, $sequence, $reservation,
            );
        }, 1);
    }

    /** @return array{out: StockMovement, in: StockMovement} */
    public function transfer(
        Product $product,
        Warehouse $source,
        Warehouse $destination,
        Account $actor,
        int $quantityMilliunits,
        ?ProductVariant $variant = null,
        ?InventoryBatch $sourceBatch = null,
        ?InventoryBatch $destinationBatch = null,
        string $condition = 'available',
        ?OperationRequest $operation = null,
        ?string $note = null,
    ): array {
        if ($source->is($destination)) {
            throw new \DomainException('Source and destination warehouses must differ.');
        }
        if ($quantityMilliunits <= 0) {
            throw new \InvalidArgumentException('Transfer quantity must be positive.');
        }

        return DB::transaction(function () use (
            $product, $source, $destination, $actor, $quantityMilliunits,
            $variant, $sourceBatch, $destinationBatch, $condition, $operation, $note,
        ): array {
            $pairs = [
                [
                    'warehouse' => $source,
                    'batch' => $sourceBatch,
                    'condition' => $condition,
                ],
                [
                    'warehouse' => $destination,
                    'batch' => $destinationBatch,
                    'condition' => $condition,
                ],
            ];
            usort($pairs, static fn (array $a, array $b): int => strcmp((string) $a['warehouse']->getKey(), (string) $b['warehouse']->getKey())
            );

            $locked = [];
            foreach ($pairs as $pair) {
                /** @var Warehouse $warehouse */
                $warehouse = $pair['warehouse'];
                /** @var InventoryBatch|null $batch */
                $batch = $pair['batch'];
                $locked[(string) $warehouse->getKey()] = $this->lockBalance(
                    $warehouse, $product, $variant, $batch, $pair['condition'],
                );
            }

            $sourceBalance = $locked[(string) $source->getKey()];
            $destinationBalance = $locked[(string) $destination->getKey()];
            if ($quantityMilliunits > $sourceBalance->availableMilliunits()) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $source->name,
                    $this->quantity->format($sourceBalance->availableMilliunits()),
                    $this->quantity->format($quantityMilliunits),
                );
            }

            $unitCost = $sourceBalance->weighted_average_cost_kobo;
            $value = (int) round(($quantityMilliunits / 1000) * $unitCost);
            $sourceQuantity = $sourceBalance->quantity_milliunits - $quantityMilliunits;
            $sourceValue = max(0, $sourceBalance->inventory_value_kobo - $value);
            $destinationQuantity = $destinationBalance->quantity_milliunits + $quantityMilliunits;
            $destinationValue = $destinationBalance->inventory_value_kobo + $value;
            $destinationAverage = $destinationQuantity > 0
                ? (int) round($destinationValue / ($destinationQuantity / 1000))
                : 0;

            $sourceBalance->forceFill([
                'quantity_milliunits' => $sourceQuantity,
                'inventory_value_kobo' => $sourceValue,
                'weighted_average_cost_kobo' => $sourceQuantity > 0 ? $unitCost : 0,
                'version' => $sourceBalance->version + 1,
                'last_movement_at' => now(),
            ])->save();
            $destinationBalance->forceFill([
                'quantity_milliunits' => $destinationQuantity,
                'inventory_value_kobo' => $destinationValue,
                'weighted_average_cost_kobo' => $destinationAverage,
                'version' => $destinationBalance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $this->syncBranchAggregate($source, $product);
            $this->syncBranchAggregate($destination, $product);

            $correlation = $operation?->getKey() ?? Str::ulid()->toString();
            $out = $this->movement(
                $product, $source, $actor, StockMovementType::TransferOut,
                -$quantityMilliunits, $sourceQuantity, $unitCost, $sourceValue,
                $variant, $sourceBatch, $condition, 'warehouse_transfer',
                (string) $correlation, (string) $correlation, null, $note, $operation, 1,
            );
            $in = $this->movement(
                $product, $destination, $actor, StockMovementType::TransferIn,
                $quantityMilliunits, $destinationQuantity, $unitCost, $destinationValue,
                $variant, $destinationBatch, $condition, 'warehouse_transfer',
                (string) $correlation, (string) $correlation, null, $note, $operation, 2,
            );

            return ['out' => $out, 'in' => $in];
        }, 1);
    }

    public function reserve(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $quantityMilliunits,
        string $referenceType,
        string $referenceId,
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        ?\DateTimeInterface $expiresAt = null,
        ?OperationRequest $operation = null,
    ): StockReservation {
        if ($quantityMilliunits <= 0) {
            throw new \InvalidArgumentException('Reservation quantity must be positive.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $actor, $quantityMilliunits,
            $referenceType, $referenceId, $variant, $batch, $expiresAt, $operation,
        ): StockReservation {
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, 'available');
            if ($quantityMilliunits > $balance->availableMilliunits()) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $warehouse->name,
                    $this->quantity->format($balance->availableMilliunits()),
                    $this->quantity->format($quantityMilliunits),
                );
            }

            $identity = hash('sha256', implode('|', [
                $referenceType, $referenceId, (string) $warehouse->getKey(),
                (string) $product->getKey(), (string) ($variant?->getKey() ?? ''),
                (string) ($batch?->getKey() ?? ''),
            ]));
            $reservation = StockReservation::query()->where('identity_hash', $identity)
                ->lockForUpdate()->first();
            if ($reservation instanceof StockReservation) {
                return $reservation;
            }

            $reservation = StockReservation::query()->create([
                'warehouse_id' => $warehouse->getKey(),
                'product_id' => $product->getKey(),
                'product_variant_id' => $variant?->getKey(),
                'inventory_batch_id' => $batch?->getKey(),
                'account_id' => $actor->getKey(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'identity_hash' => $identity,
                'quantity_milliunits' => $quantityMilliunits,
                'status' => 'active',
                'expires_at' => $expiresAt,
            ]);

            $balance->forceFill([
                'reserved_milliunits' => $balance->reserved_milliunits + $quantityMilliunits,
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $this->movement(
                $product,
                $warehouse,
                $actor,
                StockMovementType::Reservation,
                0,
                $balance->quantity_milliunits,
                $balance->weighted_average_cost_kobo,
                $balance->inventory_value_kobo,
                $variant,
                $batch,
                'available',
                'stock_reservation',
                (string) $reservation->getKey(),
                (string) $reservation->getKey(),
                'reserved',
                'Reserved stock for '.$referenceType.' '.$referenceId,
                $operation,
                1,
                $reservation,
            );

            return $reservation;
        }, 1);
    }

    public function releaseReservation(
        StockReservation $reservation,
        Account $actor,
        ?OperationRequest $operation = null,
    ): StockReservation {
        return DB::transaction(function () use (
            $reservation,
            $actor,
            $operation,
        ): StockReservation {
            /** @var StockReservation $locked */
            $locked = StockReservation::query()->whereKey($reservation->getKey())
                ->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active') {
                return $locked;
            }

            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::query()->findOrFail($locked->warehouse_id);
            /** @var Product $product */
            $product = Product::query()->findOrFail($locked->product_id);
            $variant = is_string($locked->product_variant_id)
                ? ProductVariant::query()->find($locked->product_variant_id)
                : null;
            $batch = is_string($locked->inventory_batch_id)
                ? InventoryBatch::query()->find($locked->inventory_batch_id)
                : null;
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, 'available');
            $balance->forceFill([
                'reserved_milliunits' => max(
                    0,
                    $balance->reserved_milliunits - $locked->quantity_milliunits,
                ),
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $locked->forceFill([
                'status' => 'released',
                'released_at' => now(),
            ])->save();

            $this->movement(
                $product,
                $warehouse,
                $actor,
                StockMovementType::Release,
                0,
                $balance->quantity_milliunits,
                $balance->weighted_average_cost_kobo,
                $balance->inventory_value_kobo,
                $variant,
                $batch,
                'available',
                'stock_reservation',
                (string) $locked->getKey(),
                (string) $locked->getKey(),
                'released',
                'Stock reservation released',
                $operation,
                1,
                $locked,
            );

            return $locked;
        }, 1);
    }

    /** @return array{out: StockMovement, in: StockMovement} */
    public function changeCondition(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $quantityMilliunits,
        string $fromCondition,
        string $toCondition,
        string $reasonCode,
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        ?string $note = null,
        ?OperationRequest $operation = null,
    ): array {
        if ($fromCondition === $toCondition) {
            throw new \DomainException('Stock conditions must differ.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $actor, $quantityMilliunits,
            $fromCondition, $toCondition, $reasonCode, $variant, $batch, $note,
            $operation,
        ): array {
            $conditions = [$fromCondition, $toCondition];
            sort($conditions);
            $balances = [];
            foreach ($conditions as $condition) {
                $balances[$condition] = $this->lockBalance(
                    $warehouse, $product, $variant, $batch, $condition,
                );
            }
            $from = $balances[$fromCondition];
            $to = $balances[$toCondition];

            if ($quantityMilliunits > $from->availableMilliunits()) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $warehouse->name,
                    $this->quantity->format($from->availableMilliunits()),
                    $this->quantity->format($quantityMilliunits),
                );
            }

            $unitCost = $from->weighted_average_cost_kobo;
            $value = (int) round(($quantityMilliunits / 1000) * $unitCost);
            $fromQuantity = $from->quantity_milliunits - $quantityMilliunits;
            $toQuantity = $to->quantity_milliunits + $quantityMilliunits;
            $fromValue = max(0, $from->inventory_value_kobo - $value);
            $toValue = $to->inventory_value_kobo + $value;
            $toAverage = $toQuantity > 0 ? (int) round($toValue / ($toQuantity / 1000)) : 0;

            $from->forceFill([
                'quantity_milliunits' => $fromQuantity,
                'inventory_value_kobo' => $fromValue,
                'weighted_average_cost_kobo' => $fromQuantity > 0 ? $unitCost : 0,
                'version' => $from->version + 1,
                'last_movement_at' => now(),
            ])->save();
            $to->forceFill([
                'quantity_milliunits' => $toQuantity,
                'inventory_value_kobo' => $toValue,
                'weighted_average_cost_kobo' => $toAverage,
                'version' => $to->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $correlation = (string) ($operation?->getKey() ?? Str::ulid());
            $out = $this->movement(
                $product, $warehouse, $actor, StockMovementType::TransferOut,
                -$quantityMilliunits, $fromQuantity, $unitCost, $fromValue,
                $variant, $batch, $fromCondition, 'condition_transfer', $correlation,
                $correlation, $reasonCode, $note, $operation, 1,
            );
            $in = $this->movement(
                $product, $warehouse, $actor, StockMovementType::TransferIn,
                $quantityMilliunits, $toQuantity, $unitCost, $toValue,
                $variant, $batch, $toCondition, 'condition_transfer', $correlation,
                $correlation, $reasonCode, $note, $operation, 2,
            );

            return ['out' => $out, 'in' => $in];
        }, 1);
    }

    public function countAdjustment(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $countedQuantityMilliunits,
        string $condition = 'available',
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        ?string $referenceId = null,
        ?string $note = null,
        ?OperationRequest $operation = null,
    ): ?StockMovement {
        return DB::transaction(function () use (
            $product, $warehouse, $actor, $countedQuantityMilliunits,
            $condition, $variant, $batch, $referenceId, $note, $operation,
        ): ?StockMovement {
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, $condition);
            $delta = $countedQuantityMilliunits - $balance->quantity_milliunits;
            if ($delta === 0) {
                return null;
            }

            $unitCost = $balance->weighted_average_cost_kobo;
            $newValue = max(
                0,
                (int) round(($countedQuantityMilliunits / 1000) * $unitCost),
            );
            $balance->forceFill([
                'quantity_milliunits' => $countedQuantityMilliunits,
                'inventory_value_kobo' => $newValue,
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();
            $this->syncBranchAggregate($warehouse, $product);

            return $this->movement(
                $product, $warehouse, $actor, StockMovementType::Adjustment,
                $delta, $countedQuantityMilliunits, $unitCost, $newValue,
                $variant, $batch, $condition, 'stock_count', $referenceId,
                null, 'stock_count_variance', $note, $operation, 1,
            );
        }, 1);
    }

    public function capitalizeCost(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        int $amountKobo,
        ?ProductVariant $variant = null,
        ?InventoryBatch $batch = null,
        string $condition = 'available',
        ?string $referenceId = null,
        ?OperationRequest $operation = null,
        int $sequence = 1,
    ): WarehouseStockBalance {
        if ($amountKobo <= 0) {
            throw new \InvalidArgumentException('Capitalized inventory cost must be positive.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $actor, $amountKobo, $variant, $batch,
            $condition, $referenceId, $operation, $sequence,
        ): WarehouseStockBalance {
            $balance = $this->lockBalance($warehouse, $product, $variant, $batch, $condition);
            if ($balance->quantity_milliunits <= 0) {
                throw new \DomainException('Landed cost cannot be allocated to an empty stock balance.');
            }

            $value = $balance->inventory_value_kobo + $amountKobo;
            $average = (int) round($value / ($balance->quantity_milliunits / 1000));
            $balance->forceFill([
                'inventory_value_kobo' => $value,
                'weighted_average_cost_kobo' => $average,
                'version' => $balance->version + 1,
                'last_movement_at' => now(),
            ])->save();

            $this->movement(
                $product,
                $warehouse,
                $actor,
                StockMovementType::LandedCost,
                0,
                $balance->quantity_milliunits,
                $average,
                $value,
                $variant,
                $batch,
                $condition,
                'landed_cost',
                $referenceId,
                $operation?->getKey(),
                'landed_cost_capitalized',
                'Landed cost capitalized into weighted-average inventory value',
                $operation,
                $sequence,
            );

            return $balance;
        }, 1);
    }

    public function projectLegacyMovement(StockMovement $movement): void
    {
        if ($movement->warehouse_id !== null) {
            return;
        }

        /** @var Warehouse|null $warehouse */
        $warehouse = Warehouse::query()
            ->where('branch_id', $movement->branch_id)
            ->where('is_default', true)
            ->first();
        if (! $warehouse instanceof Warehouse) {
            return;
        }

        /** @var Product $product */
        $product = Product::query()->findOrFail($movement->product_id);
        $balance = $this->lockBalance($warehouse, $product, null, null, 'available');
        $unitCost = $movement->unit_cost_kobo
            ?? $balance->weighted_average_cost_kobo
            ?? $product->default_cost_price_kobo
            ?? 0;
        $newQuantity = $balance->quantity_milliunits + $movement->quantity_delta_milliunits;
        $newValue = max(0, (int) round(($newQuantity / 1000) * $unitCost));

        $balance->forceFill([
            'quantity_milliunits' => $newQuantity,
            'weighted_average_cost_kobo' => $newQuantity > 0 ? $unitCost : 0,
            'inventory_value_kobo' => $newValue,
            'version' => $balance->version + 1,
            'last_movement_at' => $movement->occurred_at,
        ])->save();

        $movement->forceFill([
            'warehouse_id' => $warehouse->getKey(),
            'stock_condition' => 'available',
            'inventory_value_after_kobo' => $newValue,
        ])->saveQuietly();
    }

    private function lockBalance(
        Warehouse $warehouse,
        Product $product,
        ?ProductVariant $variant,
        ?InventoryBatch $batch,
        string $condition,
    ): WarehouseStockBalance {
        $identity = hash('sha256', implode('|', [
            (string) $warehouse->getKey(),
            (string) $product->getKey(),
            (string) ($variant?->getKey() ?? ''),
            (string) ($batch?->getKey() ?? ''),
            $condition,
        ]));

        WarehouseStockBalance::query()->insertOrIgnore([
            'id' => Str::ulid()->toString(),
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant?->getKey(),
            'inventory_batch_id' => $batch?->getKey(),
            'condition' => $condition,
            'identity_hash' => $identity,
            'quantity_milliunits' => 0,
            'reserved_milliunits' => 0,
            'weighted_average_cost_kobo' => $product->default_cost_price_kobo ?? 0,
            'inventory_value_kobo' => 0,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var WarehouseStockBalance $balance */
        $balance = WarehouseStockBalance::query()
            ->where('identity_hash', $identity)
            ->lockForUpdate()
            ->firstOrFail();

        return $balance;
    }

    private function syncBranchAggregate(Warehouse $warehouse, Product $product): void
    {
        $quantity = (int) WarehouseStockBalance::query()
            ->where('product_id', $product->getKey())
            ->whereIn('warehouse_id', Warehouse::query()
                ->where('branch_id', $warehouse->branch_id)
                ->select('id'))
            ->sum('quantity_milliunits');

        ProductBranchStock::query()->insertOrIgnore([
            'id' => Str::ulid()->toString(),
            'product_id' => $product->getKey(),
            'branch_id' => $warehouse->branch_id,
            'quantity_milliunits' => 0,
            'minimum_stock_milliunits' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProductBranchStock::query()
            ->where('product_id', $product->getKey())
            ->where('branch_id', $warehouse->branch_id)
            ->update([
                'quantity_milliunits' => $quantity,
                'last_movement_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function movement(
        Product $product,
        Warehouse $warehouse,
        Account $actor,
        StockMovementType $type,
        int $delta,
        int $balance,
        int $unitCost,
        int $inventoryValue,
        ?ProductVariant $variant,
        ?InventoryBatch $batch,
        string $condition,
        ?string $referenceType,
        ?string $referenceId,
        ?string $correlationId,
        ?string $reasonCode,
        ?string $note,
        ?OperationRequest $operation,
        int $sequence,
        ?StockReservation $reservation = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant?->getKey(),
            'inventory_batch_id' => $batch?->getKey(),
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->getKey(),
            'account_id' => $actor->getKey(),
            'movement_type' => $type,
            'stock_condition' => $condition,
            'quantity_delta_milliunits' => $delta,
            'balance_after_milliunits' => $balance,
            'unit_cost_kobo' => $unitCost,
            'inventory_value_after_kobo' => $inventoryValue,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'correlation_id' => $correlationId,
            'reason_code' => $reasonCode,
            'stock_reservation_id' => $reservation?->getKey(),
            'note' => $note,
            'operation_request_id' => $operation?->getKey(),
            'operation_sequence' => $operation instanceof OperationRequest ? $sequence : null,
            'occurred_at' => now(),
        ]);
    }
}
