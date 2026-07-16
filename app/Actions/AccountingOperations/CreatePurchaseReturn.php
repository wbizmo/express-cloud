<?php

declare(strict_types=1);

namespace App\Actions\AccountingOperations;

use App\Models\Account;
use App\Models\ProductBranchStock;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\StockMovement;
use App\Services\Inventory\Quantity;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreatePurchaseReturn
{
    public function __construct(
        private Quantity $quantity,
        private AuditLogger $audit,
    ) {}

    /**
     * @param list<array{
     *   purchase_receipt_line_id:string,
     *   quantity:string
     * }> $lines
     */
    public function execute(
        Request $request,
        PurchaseReceipt $purchase,
        Account $actor,
        array $lines,
        string $reason,
        ?string $supplierCreditReference,
    ): PurchaseReturn {
        return DB::transaction(function () use (
            $request,
            $purchase,
            $actor,
            $lines,
            $reason,
            $supplierCreditReference,
        ): PurchaseReturn {
            $return = PurchaseReturn::query()->create([
                'return_number' => 'PRET-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(6)),
                'purchase_receipt_id' => $purchase->getKey(),
                'supplier_id' => $purchase->supplier_id,
                'branch_id' => $purchase->branch_id,
                'processed_by_account_id' => $actor->getKey(),
                'total_kobo' => 0,
                'supplier_credit_reference' => $supplierCreditReference,
                'status' => 'completed',
                'reason' => $reason,
                'returned_at' => now(),
            ]);

            $total = 0;

            foreach ($lines as $line) {
                /** @var PurchaseReceiptLine $source */
                $source = PurchaseReceiptLine::query()
                    ->where(
                        'purchase_receipt_id',
                        $purchase->getKey(),
                    )
                    ->findOrFail(
                        $line['purchase_receipt_line_id'],
                    );

                $quantity = $this->quantity->toMilliunits(
                    $line['quantity'],
                );

                $alreadyReturned = (int) PurchaseReturnLine::query()
                    ->where(
                        'purchase_receipt_line_id',
                        $source->getKey(),
                    )
                    ->sum('quantity_milliunits');

                if (
                    $quantity <= 0
                    || $quantity + $alreadyReturned
                        > $source->quantity_milliunits
                ) {
                    throw new \DomainException(
                        'Purchase return quantity exceeds the remaining received quantity.',
                    );
                }

                $lineTotal = (int) round(
                    ($quantity / 1000) * $source->unit_cost_kobo,
                );

                PurchaseReturnLine::query()->create([
                    'purchase_return_id' => $return->getKey(),
                    'purchase_receipt_line_id' => $source->getKey(),
                    'product_id' => $source->product_id,
                    'quantity_milliunits' => $quantity,
                    'unit_cost_kobo' => $source->unit_cost_kobo,
                    'line_total_kobo' => $lineTotal,
                ]);

                /** @var ProductBranchStock|null $stock */
                $stock = ProductBranchStock::query()
                    ->where('product_id', $source->product_id)
                    ->where('branch_id', $purchase->branch_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock !== null) {
                    if ($stock->quantity_milliunits < $quantity) {
                        throw new \DomainException(
                            'The branch does not have enough stock for this purchase return.',
                        );
                    }

                    $balance = $stock->quantity_milliunits - $quantity;

                    $stock->forceFill([
                        'quantity_milliunits' => $balance,
                        'last_movement_at' => now(),
                    ])->save();

                    StockMovement::query()->create([
                        'product_id' => $source->product_id,
                        'branch_id' => $purchase->branch_id,
                        'account_id' => $actor->getKey(),
                        'movement_type' => 'purchase_return',
                        'quantity_delta_milliunits' => -$quantity,
                        'balance_after_milliunits' => $balance,
                        'reference_type' => 'purchase_return',
                        'reference_id' => $return->getKey(),
                        'note' => $reason,
                        'occurred_at' => now(),
                    ]);
                }

                $total += $lineTotal;
            }

            $return->forceFill(['total_kobo' => $total])->save();

            $this->audit->record(
                $request,
                'purchase.returned',
                'purchase_return',
                $return,
                after: [
                    'purchase_receipt_id' => $purchase->getKey(),
                    'total_kobo' => $total,
                    'reason' => $reason,
                ],
            );

            return $return->load('lines');
        }, 3);
    }
}
