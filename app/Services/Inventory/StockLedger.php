<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementType;
use App\Exceptions\Inventory\InsufficientStock;
use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use App\Services\Procurement\LowStockAlertService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class StockLedger
{
    public function __construct(
        private Quantity $quantity,
        private LowStockAlertService $alerts,
    ) {}

    public function intake(
        Product $product,
        Branch $branch,
        Account $actor,
        int $quantityMilliunits,
        ?int $unitCostKobo,
        ?string $referenceType,
        ?string $referenceId,
        ?string $note,
        ?OperationRequest $operation = null,
        int $operationSequence = 1,
    ): StockMovement {
        $this->assertTracked($product);
        $this->assertPositive($quantityMilliunits);

        return $this->apply(
            $product,
            $branch,
            $actor,
            StockMovementType::Purchase,
            $quantityMilliunits,
            $unitCostKobo,
            $referenceType,
            $referenceId,
            null,
            null,
            $note,
            false,
            $operation,
            $operationSequence,
        );
    }

    public function sale(
        Product $product,
        Branch $branch,
        Account $actor,
        int $quantityMilliunits,
        string $saleId,
        string $note,
        OperationRequest $operation,
        int $operationSequence,
    ): StockMovement {
        $this->assertTracked($product);
        $this->assertPositive($quantityMilliunits);

        return $this->apply(
            $product,
            $branch,
            $actor,
            StockMovementType::Sale,
            -$quantityMilliunits,
            null,
            'sale',
            $saleId,
            null,
            null,
            $note,
            true,
            $operation,
            $operationSequence,
        );
    }

    public function returnedSale(
        Product $product,
        Branch $branch,
        Account $actor,
        int $quantityMilliunits,
        string $saleReturnId,
        string $note,
        OperationRequest $operation,
        int $operationSequence,
    ): StockMovement {
        $this->assertTracked($product);
        $this->assertPositive($quantityMilliunits);

        return $this->apply(
            $product,
            $branch,
            $actor,
            StockMovementType::Return,
            $quantityMilliunits,
            null,
            'sale_return',
            $saleReturnId,
            null,
            null,
            $note,
            false,
            $operation,
            $operationSequence,
        );
    }

    /** @return array{out: StockMovement, in: StockMovement} */
    public function transfer(
        Product $product,
        Branch $source,
        Branch $destination,
        Account $actor,
        int $quantityMilliunits,
        ?string $note,
        ?OperationRequest $operation = null,
    ): array {
        $this->assertTracked($product);
        $this->assertPositive($quantityMilliunits);

        if ($source->is($destination)) {
            throw new \DomainException(
                'Source and destination branches must differ.',
            );
        }

        return DB::transaction(function () use (
            $product,
            $source,
            $destination,
            $actor,
            $quantityMilliunits,
            $note,
            $operation,
        ): array {
            $stocks = $this->lockBalances(
                $product,
                [$source, $destination],
            );
            $sourceStock = $stocks->get((string) $source->getKey());
            $destinationStock = $stocks->get((string) $destination->getKey());

            if (
                ! $sourceStock instanceof ProductBranchStock
                || ! $destinationStock instanceof ProductBranchStock
            ) {
                throw new \LogicException(
                    'The ordered stock-balance lock set is incomplete.',
                );
            }

            $sourceBalance = $sourceStock->quantity_milliunits
                - $quantityMilliunits;

            if ($sourceBalance < 0) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $source->name,
                    $this->quantity->format(
                        $sourceStock->quantity_milliunits,
                    ),
                    $this->quantity->format($quantityMilliunits),
                );
            }

            $destinationBalance = $destinationStock->quantity_milliunits
                + $quantityMilliunits;
            $correlationId = $operation instanceof OperationRequest
                ? (string) $operation->getKey()
                : (string) Str::ulid();

            $sourceStock->forceFill([
                'quantity_milliunits' => $sourceBalance,
                'last_movement_at' => now(),
            ])->save();
            $destinationStock->forceFill([
                'quantity_milliunits' => $destinationBalance,
                'last_movement_at' => now(),
            ])->save();

            $this->alerts->refresh($sourceStock);
            $this->alerts->refresh($destinationStock);

            $out = $this->movement(
                $product,
                $source,
                $actor,
                StockMovementType::TransferOut,
                -$quantityMilliunits,
                $sourceBalance,
                null,
                'stock_transfer',
                $correlationId,
                $correlationId,
                null,
                $note,
                $operation,
                1,
            );
            $in = $this->movement(
                $product,
                $destination,
                $actor,
                StockMovementType::TransferIn,
                $quantityMilliunits,
                $destinationBalance,
                null,
                'stock_transfer',
                $correlationId,
                $correlationId,
                null,
                $note,
                $operation,
                2,
            );

            return ['out' => $out, 'in' => $in];
        }, 1);
    }

    public function adjust(
        Product $product,
        Branch $branch,
        Account $actor,
        int $deltaMilliunits,
        string $reasonCode,
        string $note,
        ?OperationRequest $operation = null,
        int $operationSequence = 1,
    ): StockMovement {
        $this->assertTracked($product);

        if ($deltaMilliunits === 0) {
            throw new \InvalidArgumentException(
                'Adjustment quantity cannot be zero.',
            );
        }

        return $this->apply(
            $product,
            $branch,
            $actor,
            StockMovementType::Adjustment,
            $deltaMilliunits,
            null,
            'stock_adjustment',
            $operation?->getKey(),
            null,
            $reasonCode,
            $note,
            $deltaMilliunits < 0,
            $operation,
            $operationSequence,
        );
    }

    private function apply(
        Product $product,
        Branch $branch,
        Account $actor,
        StockMovementType $type,
        int $delta,
        ?int $unitCostKobo,
        ?string $referenceType,
        ?string $referenceId,
        ?string $correlationId,
        ?string $reasonCode,
        ?string $note,
        bool $rejectNegativeBalance,
        ?OperationRequest $operation,
        int $operationSequence,
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $branch,
            $actor,
            $type,
            $delta,
            $unitCostKobo,
            $referenceType,
            $referenceId,
            $correlationId,
            $reasonCode,
            $note,
            $rejectNegativeBalance,
            $operation,
            $operationSequence,
        ): StockMovement {
            $stock = ProductBranchStock::query()
                ->where('product_id', $product->getKey())
                ->where('branch_id', $branch->getKey())
                ->lockForUpdate()
                ->first();

            if (! $stock instanceof ProductBranchStock) {
                $stock = $this->createAndLockBalance($product, $branch);
            }

            $newBalance = $stock->quantity_milliunits + $delta;

            if ($rejectNegativeBalance && $newBalance < 0) {
                throw InsufficientStock::forBranch(
                    $product->name,
                    $branch->name,
                    $this->quantity->format($stock->quantity_milliunits),
                    $this->quantity->format(abs($delta)),
                );
            }

            $stock->forceFill([
                'quantity_milliunits' => $newBalance,
                'last_movement_at' => now(),
            ])->save();
            $this->alerts->refresh($stock);

            return $this->movement(
                $product,
                $branch,
                $actor,
                $type,
                $delta,
                $newBalance,
                $unitCostKobo,
                $referenceType,
                $referenceId,
                $correlationId,
                $reasonCode,
                $note,
                $operation,
                $operationSequence,
            );
        }, 1);
    }

    /**
     * @param  list<Branch>  $branches
     * @return Collection<string, ProductBranchStock>
     */
    private function lockBalances(
        Product $product,
        array $branches,
    ): Collection {
        $branchIds = collect($branches)
            ->map(static fn (Branch $branch): string => (string) $branch->getKey())
            ->unique()
            ->sort()
            ->values();

        foreach ($branchIds as $branchId) {
            ProductBranchStock::query()->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'product_id' => $product->getKey(),
                'branch_id' => $branchId,
                'quantity_milliunits' => 0,
                'minimum_stock_milliunits' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ProductBranchStock::query()
            ->where('product_id', $product->getKey())
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (ProductBranchStock $stock): string => (string) $stock->branch_id);
    }

    private function createAndLockBalance(
        Product $product,
        Branch $branch,
    ): ProductBranchStock {
        ProductBranchStock::query()->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'product_id' => $product->getKey(),
            'branch_id' => $branch->getKey(),
            'quantity_milliunits' => 0,
            'minimum_stock_milliunits' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var ProductBranchStock $stock */
        $stock = ProductBranchStock::query()
            ->where('product_id', $product->getKey())
            ->where('branch_id', $branch->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $stock;
    }

    private function movement(
        Product $product,
        Branch $branch,
        Account $actor,
        StockMovementType $type,
        int $delta,
        int $balance,
        ?int $unitCostKobo,
        ?string $referenceType,
        ?string $referenceId,
        ?string $correlationId,
        ?string $reasonCode,
        ?string $note,
        ?OperationRequest $operation,
        int $operationSequence,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->getKey(),
            'branch_id' => $branch->getKey(),
            'account_id' => $actor->getKey(),
            'movement_type' => $type,
            'quantity_delta_milliunits' => $delta,
            'balance_after_milliunits' => $balance,
            'unit_cost_kobo' => $unitCostKobo,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'correlation_id' => $correlationId,
            'reason_code' => $reasonCode,
            'note' => $note,
            'operation_request_id' => $operation?->getKey(),
            'operation_sequence' => $operation instanceof OperationRequest
                ? $operationSequence
                : null,
            'occurred_at' => now(),
        ]);
    }

    private function assertTracked(Product $product): void
    {
        if (! $product->track_inventory) {
            throw new \DomainException(
                'Untracked products cannot have stock movements.',
            );
        }
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                'Quantity must be greater than zero.',
            );
        }
    }
}
