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
use App\Models\LandedCostAllocation;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\Supplier;
use App\Models\SupplierCreditNote;
use App\Models\SupplierReturn;
use App\Models\Warehouse;
use App\Services\Accounting\AccountLocator;
use App\Services\Accounting\JournalPoster;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\WarehouseStockLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class EnterpriseProcurementWorkflow
{
    public function __construct(
        private Quantity $quantity,
        private WarehouseStockLedger $stock,
        private JournalPoster $journals,
        private AccountLocator $accounts,
    ) {}

    /**
     * @param list<array{
     *   product_id:string,
     *   product_variant_id?:string|null,
     *   quantity:string|int|float,
     *   estimated_unit_cost_kobo?:int,
     *   notes?:string|null
     * }> $lines
     */
    public function requisition(
        Account $actor,
        Warehouse $warehouse,
        string $reason,
        array $lines,
        string $priority = 'normal',
        ?string $neededOn = null,
        ?OperationRequest $operation = null,
    ): PurchaseRequisition {
        if ($lines === []) {
            throw new \InvalidArgumentException('A requisition requires at least one line.');
        }

        return DB::transaction(function () use (
            $actor, $warehouse, $reason, $lines, $priority, $neededOn, $operation,
        ): PurchaseRequisition {
            $requisition = PurchaseRequisition::query()->create([
                'requisition_number' => 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'operation_request_id' => $operation?->getKey(),
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->getKey(),
                'requested_by_account_id' => $actor->getKey(),
                'status' => 'submitted',
                'priority' => $priority,
                'needed_on' => $neededOn,
                'reason' => $reason,
                'submitted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $quantity = $this->quantity->toMilliunits($line['quantity']);
                if ($quantity <= 0) {
                    throw new \InvalidArgumentException('Requested quantities must be positive.');
                }

                PurchaseRequisitionLine::query()->create([
                    'purchase_requisition_id' => $requisition->getKey(),
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'requested_quantity_milliunits' => $quantity,
                    'approved_quantity_milliunits' => 0,
                    'estimated_unit_cost_kobo' => max(
                        0,
                        (int) ($line['estimated_unit_cost_kobo'] ?? 0),
                    ),
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $requisition->load('lines');
        }, 3);
    }

    public function approve(PurchaseRequisition $requisition, Account $actor): PurchaseRequisition
    {
        return DB::transaction(function () use ($requisition, $actor): PurchaseRequisition {
            /** @var PurchaseRequisition $locked */
            $locked = PurchaseRequisition::query()
                ->whereKey($requisition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'submitted') {
                throw new \DomainException('Only submitted requisitions can be approved.');
            }

            foreach ($locked->lines()->lockForUpdate()->get() as $line) {
                /** @var PurchaseRequisitionLine $line */
                $line->forceFill([
                    'approved_quantity_milliunits' => $line->requested_quantity_milliunits,
                ])->save();
            }

            $locked->forceFill([
                'status' => 'approved',
                'approved_by_account_id' => $actor->getKey(),
                'approved_at' => now(),
            ])->save();

            return $locked->load('lines');
        }, 3);
    }

    public function convertToOrder(
        PurchaseRequisition $requisition,
        Supplier $supplier,
        Account $actor,
        ?string $expectedAt = null,
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $requisition, $supplier, $actor, $expectedAt,
        ): PurchaseOrder {
            /** @var PurchaseRequisition $locked */
            $locked = PurchaseRequisition::query()
                ->whereKey($requisition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'approved') {
                throw new \DomainException('The requisition must be approved before conversion.');
            }

            /** @var PurchaseOrder|null $existing */
            $existing = PurchaseOrder::query()
                ->where('purchase_requisition_id', $locked->getKey())
                ->first();
            if ($existing instanceof PurchaseOrder) {
                return $existing->load('lines');
            }

            $subtotal = 0;
            $order = PurchaseOrder::query()->create([
                'order_number' => 'PO-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'supplier_id' => $supplier->getKey(),
                'branch_id' => $locked->branch_id,
                'warehouse_id' => $locked->warehouse_id,
                'purchase_requisition_id' => $locked->getKey(),
                'created_by_account_id' => $actor->getKey(),
                'approved_by_account_id' => $actor->getKey(),
                'status' => PurchaseOrderStatus::Approved,
                'approval_status' => 'approved',
                'currency' => (string) config('accounting.currency', 'NGN'),
                'expected_at' => $expectedAt,
                'subtotal_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
                'landed_cost_kobo' => 0,
                'reference_note' => 'Generated from '.$locked->requisition_number,
                'approved_at' => now(),
            ]);

            foreach ($locked->lines()->orderBy('id')->get() as $line) {
                /** @var PurchaseRequisitionLine $line */
                $quantity = $line->approved_quantity_milliunits;
                $cost = $line->estimated_unit_cost_kobo;
                $lineTotal = (int) round(($quantity / 1000) * $cost);
                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->getKey(),
                    'product_id' => $line->product_id,
                    'product_variant_id' => $line->product_variant_id,
                    'ordered_quantity_milliunits' => $quantity,
                    'received_quantity_milliunits' => 0,
                    'cancelled_quantity_milliunits' => 0,
                    'backordered_quantity_milliunits' => $quantity,
                    'unit_cost_kobo' => $cost,
                    'tax_rate_basis_points' => 0,
                    'line_total_kobo' => $lineTotal,
                    'landed_cost_allocated_kobo' => 0,
                ]);
                $subtotal += $lineTotal;
            }

            $order->forceFill([
                'subtotal_kobo' => $subtotal,
                'total_kobo' => $subtotal,
            ])->save();
            $locked->forceFill(['status' => 'converted'])->save();

            return $order->load('lines');
        }, 3);
    }

    /**
     * @param list<array{
     *   line_id:string,
     *   quantity:string|int|float,
     *   accepted_quantity?:string|int|float,
     *   quarantine_quantity?:string|int|float,
     *   batch_number?:string|null,
     *   expires_on?:string|null
     * }> $lines
     */
    public function receive(
        PurchaseOrder $order,
        Warehouse $warehouse,
        Account $actor,
        array $lines,
        ?string $supplierReference = null,
        ?string $notes = null,
        ?OperationRequest $operation = null,
    ): GoodsReceipt {
        $status = $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::from((string) $order->status);
        if (! $status->receivable()) {
            throw new \DomainException('This purchase order is not open for receipt.');
        }

        return DB::transaction(function () use (
            $order, $warehouse, $actor, $lines, $supplierReference, $notes, $operation,
        ): GoodsReceipt {
            /** @var PurchaseOrder $lockedOrder */
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if (
                $lockedOrder->warehouse_id !== null
                && (string) $lockedOrder->warehouse_id !== (string) $warehouse->getKey()
            ) {
                throw new \DomainException('Goods must be received into the purchase order warehouse.');
            }

            $receipt = GoodsReceipt::query()->create([
                'receipt_number' => 'GRN-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'operation_request_id' => $operation?->getKey(),
                'purchase_order_id' => $lockedOrder->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'received_by_account_id' => $actor->getKey(),
                'supplier_reference' => $supplierReference,
                'status' => 'received',
                'subtotal_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
                'received_at' => now(),
                'notes' => $notes,
            ]);

            $purchaseReceipt = PurchaseReceipt::query()->create([
                'receipt_number' => 'PUR-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'supplier_id' => $lockedOrder->supplier_id,
                'branch_id' => $warehouse->branch_id,
                'recorded_by_account_id' => $actor->getKey(),
                'purchase_order_id' => $lockedOrder->getKey(),
                'purchased_at' => now()->toDateString(),
                'supplier_reference' => $supplierReference,
                'subtotal_kobo' => 0,
                'discount_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
                'status' => 'recorded',
                'notes' => $notes,
            ]);
            $receipt->forceFill(['purchase_receipt_id' => $purchaseReceipt->getKey()])->save();

            $subtotal = 0;
            $taxTotal = 0;
            $movementSequence = 0;
            $orderedPayload = collect($lines)->sortBy('line_id')->values();
            foreach ($orderedPayload as $payload) {
                $line = PurchaseOrderLine::query()
                    ->where('purchase_order_id', $lockedOrder->getKey())
                    ->whereKey($payload['line_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $received = $this->quantity->toMilliunits($payload['quantity']);
                $accepted = $this->quantity->toMilliunits(
                    $payload['accepted_quantity'] ?? $payload['quantity'],
                );
                $quarantined = $this->quantity->toMilliunits(
                    $payload['quarantine_quantity'] ?? 0,
                );
                if (
                    $received <= 0
                    || $accepted < 0
                    || $quarantined < 0
                    || $accepted + $quarantined !== $received
                ) {
                    throw new \DomainException(
                        'Accepted and quarantined quantities must be non-negative and equal the received quantity.',
                    );
                }
                if ($received > $line->remainingMilliunits()) {
                    throw new \DomainException('Received quantity exceeds the outstanding order quantity.');
                }

                /** @var Product $product */
                $product = Product::query()->findOrFail($line->product_id);
                $variant = is_string($line->product_variant_id)
                    ? ProductVariant::query()->find($line->product_variant_id)
                    : null;
                $batch = null;
                $batchNumber = trim((string) ($payload['batch_number'] ?? ''));
                if ($batchNumber !== '') {
                    $batch = InventoryBatch::query()->firstOrCreate(
                        [
                            'warehouse_id' => $warehouse->getKey(),
                            'product_id' => $product->getKey(),
                            'product_variant_id' => $variant?->getKey(),
                            'batch_number' => $batchNumber,
                        ],
                        [
                            'expires_on' => $payload['expires_on'] ?? null,
                            'status' => 'available',
                        ],
                    );
                }

                $lineSubtotal = (int) round(($received / 1000) * $line->unit_cost_kobo);
                $lineTax = (int) round(
                    $lineSubtotal * ($line->tax_rate_basis_points / 10000),
                );
                $goodsLine = GoodsReceiptLine::query()->create([
                    'goods_receipt_id' => $receipt->getKey(),
                    'purchase_order_line_id' => $line->getKey(),
                    'product_id' => $product->getKey(),
                    'product_variant_id' => $variant?->getKey(),
                    'inventory_batch_id' => $batch?->getKey(),
                    'received_quantity_milliunits' => $received,
                    'accepted_quantity_milliunits' => $accepted,
                    'quarantined_quantity_milliunits' => $quarantined,
                    'unit_cost_kobo' => $line->unit_cost_kobo,
                    'tax_kobo' => $lineTax,
                    'line_total_kobo' => $lineSubtotal + $lineTax,
                ]);

                PurchaseReceiptLine::query()->create([
                    'purchase_receipt_id' => $purchaseReceipt->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity_milliunits' => $received,
                    'unit_cost_kobo' => $line->unit_cost_kobo,
                    'discount_kobo' => 0,
                    'tax_kobo' => $lineTax,
                    'line_total_kobo' => $lineSubtotal + $lineTax,
                ]);

                if ($accepted > 0 && $product->track_inventory) {
                    $this->stock->receive(
                        $product, $warehouse, $actor, $accepted,
                        $line->unit_cost_kobo, $variant, $batch, 'available',
                        'goods_receipt', (string) $receipt->getKey(), $operation,
                        ++$movementSequence, 'Accepted goods from '.$lockedOrder->order_number,
                    );
                }
                if ($quarantined > 0 && $product->track_inventory) {
                    $this->stock->receive(
                        $product, $warehouse, $actor, $quarantined,
                        $line->unit_cost_kobo, $variant, $batch, 'quarantine',
                        'goods_receipt', (string) $receipt->getKey(), $operation,
                        ++$movementSequence, 'Quarantined goods from '.$lockedOrder->order_number,
                    );
                }

                $newReceived = $line->received_quantity_milliunits + $received;
                $remaining = max(
                    0,
                    $line->ordered_quantity_milliunits
                    - $line->cancelled_quantity_milliunits
                    - $newReceived,
                );
                $line->forceFill([
                    'received_quantity_milliunits' => $newReceived,
                    'backordered_quantity_milliunits' => $remaining,
                ])->save();

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $purchaseReceipt->forceFill([
                'subtotal_kobo' => $subtotal,
                'tax_kobo' => $taxTotal,
                'total_kobo' => $subtotal + $taxTotal,
            ])->save();
            $receipt->forceFill([
                'subtotal_kobo' => $subtotal,
                'tax_kobo' => $taxTotal,
                'total_kobo' => $subtotal + $taxTotal,
            ])->save();

            $remaining = (int) $lockedOrder->lines()->sum('backordered_quantity_milliunits');
            $lockedOrder->forceFill([
                'warehouse_id' => $warehouse->getKey(),
                'status' => $remaining === 0
                    ? PurchaseOrderStatus::Received
                    : PurchaseOrderStatus::PartiallyReceived,
                'backordered_at' => $remaining > 0 ? now() : null,
                'received_at' => $remaining === 0 ? now() : null,
                'closed_at' => $remaining === 0 ? now() : null,
            ])->save();

            return $receipt->load('lines');
        }, 3);
    }

    public function allocateLandedCost(
        GoodsReceipt $receipt,
        Account $actor,
        string $costType,
        int $amountKobo,
        string $allocationMethod = 'value',
        ?OperationRequest $operation = null,
    ): LandedCostAllocation {
        if ($amountKobo <= 0) {
            throw new \InvalidArgumentException('Landed cost amount must be positive.');
        }

        return DB::transaction(function () use (
            $receipt, $actor, $costType, $amountKobo, $allocationMethod, $operation,
        ): LandedCostAllocation {
            /** @var GoodsReceipt $locked */
            $locked = GoodsReceipt::query()->whereKey($receipt->getKey())
                ->lockForUpdate()->firstOrFail();
            $lines = $locked->lines()->orderBy('id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw new \DomainException('The goods receipt has no lines to allocate.');
            }

            $basisTotal = match ($allocationMethod) {
                'quantity' => (int) $lines->sum('accepted_quantity_milliunits'),
                'equal' => $lines->count(),
                default => (int) $lines->sum('line_total_kobo'),
            };
            if ($basisTotal <= 0) {
                throw new \DomainException('The selected landed-cost allocation basis is zero.');
            }

            $allocated = 0;
            $movementSequence = 0;
            foreach ($lines as $index => $line) {
                /** @var GoodsReceiptLine $line */
                $basis = match ($allocationMethod) {
                    'quantity' => $line->accepted_quantity_milliunits,
                    'equal' => 1,
                    default => $line->line_total_kobo,
                };
                $share = $index === $lines->count() - 1
                    ? $amountKobo - $allocated
                    : (int) round($amountKobo * ($basis / $basisTotal));
                $allocated += $share;

                /** @var Product $product */
                $product = Product::query()->findOrFail($line->product_id);
                /** @var Warehouse $warehouse */
                $warehouse = Warehouse::query()->findOrFail($locked->warehouse_id);
                $variant = is_string($line->product_variant_id)
                    ? ProductVariant::query()->find($line->product_variant_id)
                    : null;
                $batch = is_string($line->inventory_batch_id)
                    ? InventoryBatch::query()->find($line->inventory_batch_id)
                    : null;
                if ($share > 0 && $line->accepted_quantity_milliunits > 0) {
                    $this->stock->capitalizeCost(
                        $product,
                        $warehouse,
                        $actor,
                        $share,
                        $variant,
                        $batch,
                        referenceId: (string) $locked->getKey(),
                        operation: $operation,
                        sequence: ++$movementSequence,
                    );
                }
                PurchaseOrderLine::query()
                    ->whereKey($line->purchase_order_line_id)
                    ->increment('landed_cost_allocated_kobo', $share);
            }

            $journal = $this->journals->post(
                CarbonImmutable::now(),
                "Landed cost {$costType} for {$locked->receipt_number}",
                [
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('inventory')->getKey(),
                        'debit_kobo' => $amountKobo,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('landed_cost_clearing')->getKey(),
                        'credit_kobo' => $amountKobo,
                        'supplier_id' => PurchaseOrder::query()
                            ->whereKey($locked->purchase_order_id)
                            ->value('supplier_id'),
                    ],
                ],
                Warehouse::query()->whereKey($locked->warehouse_id)->value('branch_id'),
                (string) $actor->getKey(),
                LandedCostAllocation::class,
                (string) $locked->getKey().':'.$costType,
                'allocated',
                null,
                null,
                'inventory',
            );

            $allocation = LandedCostAllocation::query()->create([
                'goods_receipt_id' => $locked->getKey(),
                'cost_type' => $costType,
                'allocation_method' => $allocationMethod,
                'amount_kobo' => $amountKobo,
                'created_by_account_id' => $actor->getKey(),
                'journal_entry_id' => $journal->getKey(),
                'allocated_at' => now(),
            ]);

            FinancialPosting::query()->firstOrCreate(
                [
                    'source_type' => LandedCostAllocation::class,
                    'source_id' => (string) $allocation->getKey(),
                    'source_event' => 'allocated',
                ],
                [
                    'classification' => FinancialPostingClassification::Posted,
                    'journal_entry_id' => $journal->getKey(),
                    'reason_code' => 'landed-cost-capitalized',
                    'details' => [
                        'goods_receipt_id' => (string) $locked->getKey(),
                        'amount_kobo' => $amountKobo,
                        'allocation_method' => $allocationMethod,
                    ],
                    'classified_at' => now(),
                ],
            );

            PurchaseOrder::query()->whereKey($locked->purchase_order_id)
                ->increment('landed_cost_kobo', $amountKobo);

            return $allocation;
        }, 3);
    }

    public function supplierCredit(
        SupplierReturn $return,
        Account $actor,
        string $reason,
    ): SupplierCreditNote {
        if ($return->total_kobo <= 0) {
            throw new \DomainException('Supplier credit amount must be positive.');
        }

        return SupplierCreditNote::query()->firstOrCreate(
            ['supplier_return_id' => $return->getKey()],
            [
                'credit_number' => 'SCN-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'supplier_id' => $return->supplier_id,
                'branch_id' => $return->branch_id,
                'created_by_account_id' => $actor->getKey(),
                'amount_kobo' => $return->total_kobo,
                'applied_kobo' => 0,
                'status' => 'open',
                'reason' => $reason,
                'issued_at' => now(),
            ],
        );
    }
}
