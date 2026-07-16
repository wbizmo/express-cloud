<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use Illuminate\Support\Facades\DB;

final readonly class ReceivePurchaseOrder
{
    public function __construct(
        private Quantity $quantity,
        private StockLedger $ledger,
    ) {}

    /**
     * @param list<array{
     *   line_id:mixed,
     *   quantity:mixed
     * }> $lines
     */
    public function execute(
        PurchaseOrder $order,
        Account $actor,
        string $referenceNote,
        array $lines,
    ): void {
        $status = $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::from((string) $order->status);

        if (! $status->receivable()) {
            throw new \DomainException(
                'This purchase order is not open for receipt.',
            );
        }

        DB::transaction(function () use (
            $order,
            $actor,
            $referenceNote,
            $lines,
        ): void {
            /** @var Branch $branch */
            $branch = $order->branch()->firstOrFail();

            foreach ($lines as $payload) {
                $lineId = trim((string) ($payload['line_id'] ?? ''));
                $quantity = trim((string) ($payload['quantity'] ?? ''));

                if ($lineId === '' || $quantity === '') {
                    throw new \InvalidArgumentException(
                        'Each receipt line requires a line ID and quantity.',
                    );
                }

                $line = PurchaseOrderLine::query()
                    ->where('purchase_order_id', $order->getKey())
                    ->whereKey($lineId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $receiveMilliunits = $this->quantity->toMilliunits(
                    $quantity,
                );

                if ($receiveMilliunits <= 0) {
                    throw new \InvalidArgumentException(
                        'Received quantity must be greater than zero.',
                    );
                }

                if ($receiveMilliunits > $line->remainingMilliunits()) {
                    throw new \DomainException(
                        'Received quantity exceeds the outstanding purchase quantity.',
                    );
                }

                /** @var Product $product */
                $product = $line->product()->firstOrFail();

                $this->ledger->intake(
                    $product,
                    $branch,
                    $actor,
                    $receiveMilliunits,
                    $line->unit_cost_kobo,
                    'purchase_order',
                    (string) $order->getKey(),
                    $referenceNote,
                );

                $line->forceFill([
                    'received_quantity_milliunits' => (
                        $line->received_quantity_milliunits
                        + $receiveMilliunits
                    ),
                ])->save();
            }

            $remaining = $order->lines()
                ->get()
                ->sum(
                    static fn (PurchaseOrderLine $line): int => (
                        $line->remainingMilliunits()
                    ),
                );

            $order->forceFill([
                'status' => $remaining === 0
                    ? PurchaseOrderStatus::Received
                    : PurchaseOrderStatus::PartiallyReceived,
                'received_at' => $remaining === 0 ? now() : null,
            ])->save();
        }, 3);
    }
}
