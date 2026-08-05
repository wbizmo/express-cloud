<?php

declare(strict_types=1);

namespace App\Services\Procurement;

use App\Enums\Accounting\FinancialPostingClassification;
use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Account;
use App\Models\FinancialPosting;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LandedCostAllocation;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\Warehouse;
use App\Services\Accounting\JournalPoster;
use App\Services\Inventory\WarehouseStockLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ProcurementReversalService
{
    public function __construct(
        private WarehouseStockLedger $stock,
        private JournalPoster $journals,
    ) {}

    public function voidReceipt(
        GoodsReceipt $receipt,
        Account $actor,
        string $reason,
        ?OperationRequest $operation = null,
    ): GoodsReceipt {
        return DB::transaction(function () use ($receipt, $actor, $reason, $operation): GoodsReceipt {
            /** @var GoodsReceipt $locked */
            $locked = GoodsReceipt::query()->whereKey($receipt->getKey())
                ->lockForUpdate()->firstOrFail();
            if ($locked->status === 'voided') {
                return $locked;
            }
            if ($locked->status !== 'received') {
                throw new \DomainException('Only a posted goods receipt can be voided.');
            }
            if ($locked->landedCosts()->where('status', 'active')->exists()) {
                throw new \DomainException('Reverse active landed-cost allocations before voiding this goods receipt.');
            }

            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::query()->findOrFail($locked->warehouse_id);
            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::query()->whereKey($locked->purchase_order_id)
                ->lockForUpdate()->firstOrFail();
            $sequence = 0;

            foreach ($locked->lines()->orderBy('id')->lockForUpdate()->get() as $line) {
                /** @var GoodsReceiptLine $line */
                /** @var Product $product */
                $product = Product::query()->findOrFail($line->product_id);
                $variant = is_string($line->product_variant_id)
                    ? ProductVariant::query()->find($line->product_variant_id)
                    : null;
                $batch = is_string($line->inventory_batch_id)
                    ? InventoryBatch::query()->find($line->inventory_batch_id)
                    : null;

                if ($line->accepted_quantity_milliunits > 0 && $product->track_inventory) {
                    $this->stock->reverseReceipt(
                        $product,
                        $warehouse,
                        $actor,
                        $line->accepted_quantity_milliunits,
                        $variant,
                        $batch,
                        'available',
                        'goods_receipt_void',
                        (string) $locked->getKey(),
                        $operation,
                        ++$sequence,
                        $reason,
                    );
                }
                if ($line->quarantined_quantity_milliunits > 0 && $product->track_inventory) {
                    $this->stock->reverseReceipt(
                        $product,
                        $warehouse,
                        $actor,
                        $line->quarantined_quantity_milliunits,
                        $variant,
                        $batch,
                        'quarantine',
                        'goods_receipt_void',
                        (string) $locked->getKey(),
                        $operation,
                        ++$sequence,
                        $reason,
                    );
                }

                /** @var PurchaseOrderLine $orderLine */
                $orderLine = PurchaseOrderLine::query()
                    ->whereKey($line->purchase_order_line_id)
                    ->lockForUpdate()->firstOrFail();
                $newReceived = max(
                    0,
                    $orderLine->received_quantity_milliunits - $line->received_quantity_milliunits,
                );
                $orderLine->forceFill([
                    'received_quantity_milliunits' => $newReceived,
                    'backordered_quantity_milliunits' => max(
                        0,
                        $orderLine->ordered_quantity_milliunits
                            - $orderLine->cancelled_quantity_milliunits
                            - $newReceived,
                    ),
                ])->save();
            }

            if (is_string($locked->purchase_receipt_id)) {
                /** @var PurchaseReceipt|null $purchaseReceipt */
                $purchaseReceipt = PurchaseReceipt::query()
                    ->whereKey($locked->purchase_receipt_id)->lockForUpdate()->first();
                if ($purchaseReceipt instanceof PurchaseReceipt) {
                    $reversal = $this->reversePostingJournal(
                        PurchaseReceipt::class,
                        (string) $purchaseReceipt->getKey(),
                        'recorded',
                        'voided',
                        $actor,
                        $operation,
                        'Void purchase receipt '.$purchaseReceipt->receipt_number,
                    );
                    $purchaseReceipt->forceFill([
                        'status' => 'voided',
                        'notes' => trim(implode("\n\n", array_filter([
                            $purchaseReceipt->notes,
                            'Voided: '.$reason,
                        ]))),
                    ])->save();
                    if ($reversal instanceof JournalEntry) {
                        FinancialPosting::query()->firstOrCreate([
                            'source_type' => PurchaseReceipt::class,
                            'source_id' => (string) $purchaseReceipt->getKey(),
                            'source_event' => 'voided',
                        ], [
                            'classification' => FinancialPostingClassification::Posted,
                            'journal_entry_id' => $reversal->getKey(),
                            'reason_code' => 'purchase-receipt-voided',
                            'details' => ['goods_receipt_id' => (string) $locked->getKey()],
                            'classified_at' => now(),
                        ]);
                    }
                }
            }

            $received = (int) $order->lines()->sum('received_quantity_milliunits');
            $remaining = (int) $order->lines()->get()->sum(
                static fn (PurchaseOrderLine $line): int => $line->remainingMilliunits(),
            );
            $order->forceFill([
                'status' => $received === 0
                    ? PurchaseOrderStatus::Approved
                    : ($remaining > 0 ? PurchaseOrderStatus::PartiallyReceived : PurchaseOrderStatus::Received),
                'received_at' => $received > 0 && $remaining === 0 ? now() : null,
                'backordered_at' => $remaining > 0 ? now() : null,
                'closed_at' => $received > 0 && $remaining === 0 ? now() : null,
            ])->save();

            $locked->forceFill([
                'status' => 'voided',
                'voided_by_account_id' => $actor->getKey(),
                'voided_at' => now(),
                'void_reason' => trim($reason),
            ])->save();

            return $locked->fresh(['lines']) ?? $locked;
        }, 3);
    }

    public function reverseLandedCost(
        LandedCostAllocation $allocation,
        Account $actor,
        string $reason,
        ?OperationRequest $operation = null,
    ): LandedCostAllocation {
        return DB::transaction(function () use ($allocation, $actor, $reason, $operation): LandedCostAllocation {
            /** @var LandedCostAllocation $locked */
            $locked = LandedCostAllocation::query()->whereKey($allocation->getKey())
                ->lockForUpdate()->firstOrFail();
            if ($locked->status === 'reversed') {
                return $locked;
            }
            if ($locked->status !== 'active') {
                throw new \DomainException('Only an active landed-cost allocation can be reversed.');
            }
            /** @var GoodsReceipt $receipt */
            $receipt = GoodsReceipt::query()->whereKey($locked->goods_receipt_id)
                ->lockForUpdate()->firstOrFail();
            if ($receipt->status === 'voided') {
                throw new \DomainException('A landed-cost allocation cannot be reversed after its receipt is voided.');
            }
            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::query()->findOrFail($receipt->warehouse_id);
            $lines = $receipt->lines()->orderBy('id')->lockForUpdate()->get();
            $basisTotal = match ($locked->allocation_method) {
                'quantity' => (int) $lines->sum('accepted_quantity_milliunits'),
                'equal' => $lines->count(),
                default => (int) $lines->sum('line_total_kobo'),
            };
            if ($basisTotal <= 0) {
                throw new \DomainException('The landed-cost allocation basis is no longer reversible.');
            }

            $reversed = 0;
            $sequence = 0;
            foreach ($lines as $index => $line) {
                /** @var GoodsReceiptLine $line */
                $basis = match ($locked->allocation_method) {
                    'quantity' => $line->accepted_quantity_milliunits,
                    'equal' => 1,
                    default => $line->line_total_kobo,
                };
                $share = $index === $lines->count() - 1
                    ? $locked->amount_kobo - $reversed
                    : (int) round($locked->amount_kobo * ($basis / $basisTotal));
                $reversed += $share;
                if ($share <= 0 || $line->accepted_quantity_milliunits <= 0) {
                    continue;
                }
                /** @var Product $product */
                $product = Product::query()->findOrFail($line->product_id);
                $variant = is_string($line->product_variant_id)
                    ? ProductVariant::query()->find($line->product_variant_id)
                    : null;
                $batch = is_string($line->inventory_batch_id)
                    ? InventoryBatch::query()->find($line->inventory_batch_id)
                    : null;
                $this->stock->reverseCapitalizedCost(
                    $product,
                    $warehouse,
                    $actor,
                    $share,
                    $variant,
                    $batch,
                    referenceId: (string) $locked->getKey(),
                    operation: $operation,
                    sequence: ++$sequence,
                    note: $reason,
                );
                PurchaseOrderLine::query()->whereKey($line->purchase_order_line_id)
                    ->decrement('landed_cost_allocated_kobo', $share);
            }

            /** @var JournalEntry|null $original */
            $original = is_string($locked->journal_entry_id)
                ? JournalEntry::query()->find($locked->journal_entry_id)
                : null;
            $reversal = $original instanceof JournalEntry
                ? $this->reverseJournal(
                    $original,
                    $actor,
                    LandedCostAllocation::class,
                    (string) $locked->getKey(),
                    'reversed',
                    $operation,
                    'Reverse landed cost '.$locked->cost_type,
                )
                : null;

            PurchaseOrder::query()->whereKey($receipt->purchase_order_id)
                ->decrement('landed_cost_kobo', min(
                    $locked->amount_kobo,
                    (int) PurchaseOrder::query()->whereKey($receipt->purchase_order_id)
                        ->value('landed_cost_kobo'),
                ));
            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by_account_id' => $actor->getKey(),
                'reversal_journal_entry_id' => $reversal?->getKey(),
                'reversed_at' => now(),
                'reversal_reason' => trim($reason),
            ])->save();

            FinancialPosting::query()->firstOrCreate([
                'source_type' => LandedCostAllocation::class,
                'source_id' => (string) $locked->getKey(),
                'source_event' => 'reversed',
            ], [
                'classification' => $reversal instanceof JournalEntry
                    ? FinancialPostingClassification::Posted
                    : FinancialPostingClassification::NonPosting,
                'journal_entry_id' => $reversal?->getKey(),
                'reason_code' => 'landed-cost-reversed',
                'details' => ['amount_kobo' => $locked->amount_kobo],
                'classified_at' => now(),
            ]);

            return $locked;
        }, 3);
    }

    private function reversePostingJournal(
        string $sourceType,
        string $sourceId,
        string $sourceEvent,
        string $reversalEvent,
        Account $actor,
        ?OperationRequest $operation,
        string $memo,
    ): ?JournalEntry {
        /** @var FinancialPosting|null $posting */
        $posting = FinancialPosting::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('source_event', $sourceEvent)
            ->first();
        /** @var JournalEntry|null $journal */
        $journal = $posting?->journal_entry_id !== null
            ? JournalEntry::query()->find($posting->journal_entry_id)
            : null;

        return $journal instanceof JournalEntry
            ? $this->reverseJournal($journal, $actor, $sourceType, $sourceId, $reversalEvent, $operation, $memo)
            : null;
    }

    private function reverseJournal(
        JournalEntry $original,
        Account $actor,
        string $sourceType,
        string $sourceId,
        string $sourceEvent,
        ?OperationRequest $operation,
        string $memo,
    ): JournalEntry {
        $lines = $original->lines()->orderBy('id')->get()->map(
            static fn (JournalLine $line): array => [
                'account_id' => (string) $line->ledger_account_id,
                'branch_id' => $line->branch_id,
                'customer_id' => $line->customer_id,
                'supplier_id' => $line->supplier_id,
                'warehouse_id' => $line->warehouse_id,
                'tax_rate_id' => $line->tax_rate_id,
                'tax_basis_kobo' => $line->tax_basis_kobo,
                'tax_amount_kobo' => $line->tax_amount_kobo,
                'subledger_reference' => $line->subledger_reference,
                'debit_kobo' => $line->credit_kobo,
                'credit_kobo' => $line->debit_kobo,
                'description' => 'Reversal: '.$line->description,
            ],
        )->all();

        return $this->journals->post(
            CarbonImmutable::now(),
            $memo,
            $lines,
            $original->branch_id,
            (string) $actor->getKey(),
            $sourceType,
            $sourceId,
            $sourceEvent,
            $operation?->getKey(),
            $operation instanceof OperationRequest ? 90 : null,
            $original->book_type,
        );
    }
}
