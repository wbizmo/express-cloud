<?php

declare(strict_types=1);

namespace App\Actions\Commercial;

use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateSaleReturn
{
    public function __construct(
        private Quantity $quantity,
        private AuditLogger $audit,
        private StockLedger $stock,
        private CommandBoundary $commands,
    ) {}

    /**
     * @param  list<array{sale_item_id: string, quantity: string|int|float, restock?: bool}>  $lines
     */
    public function execute(
        Request $request,
        Sale $sale,
        Account $actor,
        array $lines,
        string $reason,
        ?string $refundMethod,
        ?string $idempotencyKey = null,
    ): SaleReturn {
        $idempotencyKey ??= 'sale-return-'.hash(
            'sha256',
            (string) $sale->getKey().'|'.$reason.'|'.json_encode($lines),
        );

        $result = $this->commands->execute(
            'sale.return',
            $idempotencyKey,
            [
                'sale_id' => (string) $sale->getKey(),
                'lines' => $lines,
                'reason' => $reason,
                'refund_method' => $refundMethod,
            ],
            $actor,
            (string) $sale->branch_id,
            function (OperationRequest $operation) use (
                $request,
                $sale,
                $actor,
                $lines,
                $reason,
                $refundMethod,
            ): SaleReturn {
                /** @var Sale $lockedSale */
                $lockedSale = Sale::query()
                    ->whereKey($sale->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                /** @var Branch $branch */
                $branch = Branch::query()
                    ->whereKey($lockedSale->branch_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $normalizedLines = $this->normalizedLines($lines);
                $itemIds = collect($normalizedLines)
                    ->pluck('sale_item_id')
                    ->unique()
                    ->sort()
                    ->values();
                $saleItems = SaleItem::query()
                    ->where('sale_id', $lockedSale->getKey())
                    ->whereIn('id', $itemIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $return = SaleReturn::query()->create([
                    'return_code' => 'RET-'.now()->format('ymd').'-'
                        .Str::upper(Str::random(6)),
                    'sale_id' => $lockedSale->getKey(),
                    'branch_id' => $lockedSale->branch_id,
                    'customer_id' => $lockedSale->customer_id,
                    'processed_by_account_id' => $actor->getKey(),
                    'total_refund_kobo' => 0,
                    'refund_method' => $refundMethod,
                    'status' => 'completed',
                    'reason' => $reason,
                    'operation_request_id' => $operation->getKey(),
                    'returned_at' => now(),
                ]);
                $totalRefund = 0;
                $movementSequence = 0;

                foreach ($normalizedLines as $line) {
                    $saleItem = $saleItems->get($line['sale_item_id']);

                    if (! $saleItem instanceof SaleItem) {
                        throw new \DomainException(
                            'A selected sale line no longer exists.',
                        );
                    }

                    $quantity = $this->quantity->toMilliunits(
                        (string) $line['quantity'],
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
                        /** @var Product $product */
                        $product = Product::query()
                            ->whereKey($saleItem->product_id)
                            ->lockForUpdate()
                            ->firstOrFail();
                        $movementSequence++;
                        $this->stock->returnedSale(
                            $product,
                            $branch,
                            $actor,
                            $quantity,
                            (string) $return->getKey(),
                            $reason,
                            $operation,
                            $movementSequence,
                        );
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
                        'sale_id' => $lockedSale->getKey(),
                        'refund_kobo' => $totalRefund,
                        'reason' => $reason,
                        'operation_id' => (string) $operation->getKey(),
                    ],
                );

                return $return;
            },
        );

        if (! $result instanceof SaleReturn) {
            throw new \LogicException(
                'The sale return command returned an invalid result.',
            );
        }

        return $result->load('items');
    }

    /**
     * @param  list<array{sale_item_id: string, quantity: string|int|float, restock?: bool}>  $lines
     * @return list<array{sale_item_id: string, quantity: string|int|float, restock?: bool}>
     */
    private function normalizedLines(array $lines): array
    {
        usort(
            $lines,
            static fn (array $left, array $right): int => strcmp(
                $left['sale_item_id'],
                $right['sale_item_id'],
            ),
        );

        return $lines;
    }
}
