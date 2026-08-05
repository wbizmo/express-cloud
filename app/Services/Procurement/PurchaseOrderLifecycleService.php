<?php

declare(strict_types=1);

namespace App\Services\Procurement;

use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseOrderLifecycleService
{
    public function __construct(
        private Quantity $quantity,
        private MoneyInput $money,
    ) {}

    /**
     * @param array{
     *   supplier_id:string,
     *   branch_id:string,
     *   expected_at?:string|null,
     *   reference_note:string,
     *   lines:list<array{
     *     product_id:string,
     *     quantity:string|int|float,
     *     unit_cost:string|int|float,
     *     tax_rate_percent?:string|int|float|null
     *   }>
     * } $payload
     */
    public function revise(
        PurchaseOrder $order,
        Account $actor,
        array $payload,
    ): PurchaseOrder {
        return DB::transaction(function () use ($order, $payload): PurchaseOrder {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $status = $this->status($locked);

            if (! in_array($status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Approved], true)) {
                throw new \DomainException('Only draft or unreceived approved purchase orders can be edited.');
            }
            if ($locked->lines()->where('received_quantity_milliunits', '>', 0)->exists()) {
                throw new \DomainException('A purchase order cannot be edited after goods have been received. Cancel only the outstanding quantity instead.');
            }
            if (($payload['lines'] ?? []) === []) {
                throw new \InvalidArgumentException('A purchase order requires at least one line.');
            }

            $subtotal = 0;
            $tax = 0;
            $locked->lines()->delete();

            foreach ($payload['lines'] as $line) {
                $quantityMilliunits = $this->quantity->toMilliunits($line['quantity']);
                if ($quantityMilliunits <= 0) {
                    throw new \InvalidArgumentException('Purchase-order quantities must be greater than zero.');
                }
                $unitCostKobo = $this->money->toKobo($line['unit_cost']) ?? 0;
                $taxBasisPoints = (int) round((float) ($line['tax_rate_percent'] ?? 0) * 100);
                $lineSubtotal = (int) round(($quantityMilliunits / 1000) * $unitCostKobo);
                $lineTax = (int) round($lineSubtotal * ($taxBasisPoints / 10000));

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $locked->getKey(),
                    'product_id' => $line['product_id'],
                    'ordered_quantity_milliunits' => $quantityMilliunits,
                    'received_quantity_milliunits' => 0,
                    'cancelled_quantity_milliunits' => 0,
                    'backordered_quantity_milliunits' => $quantityMilliunits,
                    'unit_cost_kobo' => $unitCostKobo,
                    'tax_rate_basis_points' => $taxBasisPoints,
                    'line_total_kobo' => $lineSubtotal + $lineTax,
                    'landed_cost_allocated_kobo' => 0,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $locked->forceFill([
                'supplier_id' => $payload['supplier_id'],
                'branch_id' => $payload['branch_id'],
                'status' => PurchaseOrderStatus::Draft,
                'approval_status' => 'pending',
                'approved_by_account_id' => null,
                'approved_at' => null,
                'expected_at' => $payload['expected_at'] ?? null,
                'reference_note' => trim($payload['reference_note']),
                'subtotal_kobo' => $subtotal,
                'tax_kobo' => $tax,
                'total_kobo' => $subtotal + $tax,
                'received_at' => null,
                'backordered_at' => null,
                'closed_at' => null,
            ])->save();

            return $locked->fresh(['lines', 'supplier', 'branch']) ?? $locked;
        }, 3);
    }

    public function cancel(
        PurchaseOrder $order,
        Account $actor,
        string $reason,
    ): PurchaseOrder {
        return DB::transaction(function () use ($order, $actor, $reason): PurchaseOrder {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $status = $this->status($locked);

            if (! in_array($status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Approved], true)) {
                throw new \DomainException('Only draft or unreceived approved purchase orders can be cancelled.');
            }
            if ($locked->lines()->where('received_quantity_milliunits', '>', 0)->exists()) {
                throw new \DomainException('Received purchase orders cannot be cancelled. Cancel only their outstanding balance.');
            }

            foreach ($locked->lines()->lockForUpdate()->get() as $line) {
                /** @var PurchaseOrderLine $line */
                $line->forceFill([
                    'cancelled_quantity_milliunits' => $line->ordered_quantity_milliunits,
                    'backordered_quantity_milliunits' => 0,
                ])->save();
            }

            $locked->forceFill([
                'status' => PurchaseOrderStatus::Cancelled,
                'approval_status' => 'cancelled',
                'closed_at' => now(),
                'reference_note' => $this->appendReason($locked->reference_note, 'Cancelled', $reason, $actor),
            ])->save();

            return $locked->fresh(['lines']) ?? $locked;
        }, 3);
    }

    public function cancelOutstanding(
        PurchaseOrder $order,
        Account $actor,
        string $reason,
    ): PurchaseOrder {
        return DB::transaction(function () use ($order, $actor, $reason): PurchaseOrder {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $status = $this->status($locked);

            if (! in_array($status, [PurchaseOrderStatus::Approved, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw new \DomainException('Only approved or partially received purchase orders can close an outstanding balance.');
            }

            $received = 0;
            $cancelled = 0;
            foreach ($locked->lines()->lockForUpdate()->get() as $line) {
                /** @var PurchaseOrderLine $line */
                $received += $line->received_quantity_milliunits;
                $remaining = $line->remainingMilliunits();
                if ($remaining <= 0) {
                    continue;
                }
                $line->forceFill([
                    'cancelled_quantity_milliunits' => $line->cancelled_quantity_milliunits + $remaining,
                    'backordered_quantity_milliunits' => 0,
                ])->save();
                $cancelled += $remaining;
            }
            if ($cancelled === 0) {
                throw new \DomainException('This purchase order has no outstanding quantity to cancel.');
            }

            $locked->forceFill([
                'status' => $received > 0
                    ? PurchaseOrderStatus::PartiallyCancelled
                    : PurchaseOrderStatus::Cancelled,
                'approval_status' => 'closed',
                'closed_at' => now(),
                'backordered_at' => null,
                'reference_note' => $this->appendReason($locked->reference_note, 'Outstanding balance cancelled', $reason, $actor),
            ])->save();

            return $locked->fresh(['lines']) ?? $locked;
        }, 3);
    }

    public function editable(PurchaseOrder $order): bool
    {
        $status = $this->status($order);

        return in_array($status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Approved], true)
            && ! $order->lines()->where('received_quantity_milliunits', '>', 0)->exists();
    }

    private function status(PurchaseOrder $order): PurchaseOrderStatus
    {
        return $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::from((string) $order->status);
    }

    private function appendReason(
        ?string $existing,
        string $action,
        string $reason,
        Account $actor,
    ): string {
        $line = sprintf(
            '%s by %s on %s. Reason: %s',
            $action,
            trim($actor->first_name.' '.$actor->last_name),
            now()->toIso8601String(),
            trim($reason),
        );

        return trim(implode("\n\n", array_filter([$existing, $line])));
    }
}
