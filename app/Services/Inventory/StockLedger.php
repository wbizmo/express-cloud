<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementType;
use App\Exceptions\Inventory\InsufficientStock;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class StockLedger
{
    public function __construct(private Quantity $quantity) {}

    public function intake(
        Product $product,
        Branch $branch,
        Account $actor,
        int $quantityMilliunits,
        ?int $unitCostKobo,
        ?string $referenceType,
        ?string $referenceId,
        ?string $note,
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
        );
    }

    /**
     * @return array{out:StockMovement,in:StockMovement}
     */
    public function transfer(
        Product $product,
        Branch $source,
        Branch $destination,
        Account $actor,
        int $quantityMilliunits,
        ?string $note,
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
        ): array {
            $correlationId = (string) Str::ulid();

            $out = $this->apply(
                $product,
                $source,
                $actor,
                StockMovementType::TransferOut,
                -$quantityMilliunits,
                null,
                'stock_transfer',
                $correlationId,
                $correlationId,
                null,
                $note,
                true,
            );

            $in = $this->apply(
                $product,
                $destination,
                $actor,
                StockMovementType::TransferIn,
                $quantityMilliunits,
                null,
                'stock_transfer',
                $correlationId,
                $correlationId,
                null,
                $note,
                false,
            );

            return ['out' => $out, 'in' => $in];
        });
    }

    public function adjust(
        Product $product,
        Branch $branch,
        Account $actor,
        int $deltaMilliunits,
        string $reasonCode,
        string $note,
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
            null,
            null,
            $reasonCode,
            $note,
            $deltaMilliunits < 0,
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
        ): StockMovement {
            $stock = ProductBranchStock::query()
                ->where('product_id', $product->getKey())
                ->where('branch_id', $branch->getKey())
                ->lockForUpdate()
                ->first();

            if (! $stock instanceof ProductBranchStock) {
                $stock = ProductBranchStock::query()->create([
                    'product_id' => $product->getKey(),
                    'branch_id' => $branch->getKey(),
                    'quantity_milliunits' => 0,
                    'minimum_stock_milliunits' => 5000,
                ]);

                $stock = ProductBranchStock::query()
                    ->whereKey($stock->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
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

            return StockMovement::query()->create([
                'product_id' => $product->getKey(),
                'branch_id' => $branch->getKey(),
                'account_id' => $actor->getKey(),
                'movement_type' => $type,
                'quantity_delta_milliunits' => $delta,
                'balance_after_milliunits' => $newBalance,
                'unit_cost_kobo' => $unitCostKobo,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'correlation_id' => $correlationId,
                'reason_code' => $reasonCode,
                'note' => $note,
                'occurred_at' => now(),
            ]);
        }, 3);
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
