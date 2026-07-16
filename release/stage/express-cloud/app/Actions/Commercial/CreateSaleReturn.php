<?php

declare(strict_types=1);

namespace App\Actions\Commercial;

use App\Enums\Inventory\StockMovementType;
use App\Models\Account;
use App\Models\ProductBranchStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Services\Inventory\Quantity;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateSaleReturn
{
    public function __construct(
        private Quantity $quantity,
        private AuditLogger $audit,
    ) {}

    /**
     * @param list<array{
     *   sale_item_id:string,
     *   quantity:string,
     *   restock?:bool
     * }> $lines
     */
    public function execute(
        Request $request,
        Sale $sale,
        Account $actor,
        array $lines,
        string $reason,
        ?string $refundMethod,
    ): SaleReturn {
        return DB::transaction(function () use (
            $request,
            $sale,
            $actor,
            $lines,
            $reason,
            $refundMethod,
        ): SaleReturn {
            $return = SaleReturn::query()->create([
                'return_code' => 'RET-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(6)),
                'sale_id' => $sale->getKey(),
                'branch_id' => $sale->branch_id,
                'customer_id' => $sale->customer_id,
                'processed_by_account_id' => $actor->getKey(),
                'total_refund_kobo' => 0,
                'refund_method' => $refundMethod,
                'status' => 'completed',
                'reason' => $reason,
                'returned_at' => now(),
            ]);

            $totalRefund = 0;

            foreach ($lines as $line) {
                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::query()
                    ->where('sale_id', $sale->getKey())
                    ->findOrFail($line['sale_item_id']);

                $quantity = $this->quantity->toMilliunits(
                    $line['quantity'],
                );

                $alreadyReturned = (int) SaleReturnItem::query()
                    ->where('sale_item_id', $saleItem->getKey())
                    ->sum('quantity_milliunits');

                if (
                    $quantity <= 0
                    || $quantity + $alreadyReturned
                        > $saleItem->quantity_milliunits
                ) {
                    throw new \DomainException(
                        'Return quantity exceeds the remaining sold quantity.',
                    );
                }

                $refund = (int) round(
                    $saleItem->line_total_kobo
                    * ($quantity / $saleItem->quantity_milliunits),
                );
                $restock = (bool) ($line['restock'] ?? true);

                SaleReturnItem::query()->create([
                    'sale_return_id' => $return->getKey(),
                    'sale_item_id' => $saleItem->getKey(),
                    'product_id' => $saleItem->product_id,
                    'quantity_milliunits' => $quantity,
                    'refund_amount_kobo' => $refund,
                    'restock' => $restock,
                ]);

                if ($saleItem->track_inventory_snapshot && $restock) {
                    /** @var ProductBranchStock $stock */
                    $stock = ProductBranchStock::query()
                        ->where('product_id', $saleItem->product_id)
                        ->where('branch_id', $sale->branch_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $balance = $stock->quantity_milliunits + $quantity;

                    $stock->forceFill([
                        'quantity_milliunits' => $balance,
                        'last_movement_at' => now(),
                    ])->save();

                    StockMovement::query()->create([
                        'product_id' => $saleItem->product_id,
                        'branch_id' => $sale->branch_id,
                        'account_id' => $actor->getKey(),
                        'movement_type' => StockMovementType::Return,
                        'quantity_delta_milliunits' => $quantity,
                        'balance_after_milliunits' => $balance,
                        'reference_type' => 'sale_return',
                        'reference_id' => $return->getKey(),
                        'note' => $reason,
                        'occurred_at' => now(),
                    ]);
                }

                $totalRefund += $refund;
            }

            $return->forceFill([
                'total_refund_kobo' => $totalRefund,
            ])->save();

            $this->audit->record(
                $request,
                'sale.returned',
                'sale_return',
                $return,
                after: [
                    'sale_id' => $sale->getKey(),
                    'refund_kobo' => $totalRefund,
                    'reason' => $reason,
                ],
            );

            return $return->load('items');
        }, 3);
    }
}
