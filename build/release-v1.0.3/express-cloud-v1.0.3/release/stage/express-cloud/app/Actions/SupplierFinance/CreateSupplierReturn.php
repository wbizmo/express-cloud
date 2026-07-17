<?php

declare(strict_types=1);

namespace App\Actions\SupplierFinance;

use App\Enums\Inventory\StockMovementType;
use App\Enums\SupplierFinance\SupplierReturnStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnLine;
use App\Services\Inventory\Quantity;
use App\Services\Procurement\LowStockAlertService;
use App\Services\SupplierFinance\SupplierReturnNumberGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplierReturn
{
    public function __construct(
        private SupplierReturnNumberGenerator $numbers,
        private Quantity $quantity,
        private LowStockAlertService $alerts,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function execute(
        Account $actor,
        string $supplierId,
        string $branchId,
        ?string $supplierBillId,
        string $reason,
        string $referenceNote,
        array $lines,
    ): SupplierReturn {
        return DB::transaction(function () use (
            $actor,
            $supplierId,
            $branchId,
            $supplierBillId,
            $reason,
            $referenceNote,
            $lines,
        ): SupplierReturn {
            /** @var Branch $branch */
            $branch = Branch::query()->findOrFail($branchId);

            if ($supplierBillId !== null) {
                SupplierBill::query()
                    ->where('supplier_id', $supplierId)
                    ->where('branch_id', $branchId)
                    ->findOrFail($supplierBillId);
            }

            $return = SupplierReturn::query()->create([
                'return_number' => $this->numbers->generate(),
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'supplier_bill_id' => $supplierBillId,
                'created_by_account_id' => $actor->getKey(),
                'status' => SupplierReturnStatus::Confirmed,
                'return_date' => today(),
                'total_kobo' => 0,
                'reason' => $reason,
                'reference_note' => $referenceNote,
                'confirmed_at' => now(),
            ]);

            $total = 0;

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = Product::query()->findOrFail(
                    (string) ($line['product_id'] ?? ''),
                );

                if (! $product->track_inventory) {
                    throw new \DomainException(
                        'Untracked products cannot be returned through inventory.',
                    );
                }

                $quantityMilliunits = $this->quantity->toMilliunits(
                    (string) ($line['quantity'] ?? ''),
                );

                if ($quantityMilliunits <= 0) {
                    throw new \InvalidArgumentException(
                        'Supplier return quantity must be greater than zero.',
                    );
                }

                $unitCostKobo = (int) ($line['unit_cost_kobo'] ?? 0);
                $lineTotal = (int) round(
                    ($quantityMilliunits / 1000) * $unitCostKobo,
                );

                $stock = ProductBranchStock::query()
                    ->where('product_id', $product->getKey())
                    ->where('branch_id', $branch->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $stock instanceof ProductBranchStock) {
                    throw new \DomainException(
                        'No branch stock exists for the returned product.',
                    );
                }

                $newBalance = $stock->quantity_milliunits
                    - $quantityMilliunits;

                if ($newBalance < 0) {
                    throw new \DomainException(
                        'Supplier return quantity exceeds available stock.',
                    );
                }

                $stock->forceFill([
                    'quantity_milliunits' => $newBalance,
                    'last_movement_at' => now(),
                ])->save();

                $this->alerts->refresh($stock);

                SupplierReturnLine::query()->create([
                    'supplier_return_id' => $return->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity_milliunits' => $quantityMilliunits,
                    'unit_cost_kobo' => $unitCostKobo,
                    'line_total_kobo' => $lineTotal,
                ]);

                StockMovement::query()->create([
                    'product_id' => $product->getKey(),
                    'branch_id' => $branch->getKey(),
                    'account_id' => $actor->getKey(),
                    'movement_type' => StockMovementType::Return,
                    'quantity_delta_milliunits' => -$quantityMilliunits,
                    'balance_after_milliunits' => $newBalance,
                    'unit_cost_kobo' => $unitCostKobo,
                    'reference_type' => 'supplier_return',
                    'reference_id' => $return->getKey(),
                    'reason_code' => $reason,
                    'note' => $referenceNote,
                    'occurred_at' => now(),
                ]);

                $total += $lineTotal;
            }

            $return->forceFill([
                'total_kobo' => $total,
            ])->save();

            return $return;
        }, 3);
    }
}
