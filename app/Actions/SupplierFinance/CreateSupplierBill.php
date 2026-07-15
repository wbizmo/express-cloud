<?php

declare(strict_types=1);

namespace App\Actions\SupplierFinance;

use App\Enums\SupplierFinance\SupplierBillStatus;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\SupplierBill;
use App\Models\SupplierBillLine;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\SupplierFinance\SupplierBillNumberGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplierBill
{
    public function __construct(
        private SupplierBillNumberGenerator $numbers,
        private Quantity $quantity,
        private MoneyInput $money,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function execute(
        Account $actor,
        string $supplierId,
        string $branchId,
        ?string $purchaseOrderId,
        string $billDate,
        ?string $dueDate,
        ?string $supplierReference,
        string $referenceNote,
        array $lines,
    ): SupplierBill {
        return DB::transaction(function () use (
            $actor,
            $supplierId,
            $branchId,
            $purchaseOrderId,
            $billDate,
            $dueDate,
            $supplierReference,
            $referenceNote,
            $lines,
        ): SupplierBill {
            if ($purchaseOrderId !== null) {
                PurchaseOrder::query()
                    ->where('supplier_id', $supplierId)
                    ->where('branch_id', $branchId)
                    ->findOrFail($purchaseOrderId);
            }

            $bill = SupplierBill::query()->create([
                'bill_number' => $this->numbers->generate(),
                'supplier_reference' => $supplierReference,
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'purchase_order_id' => $purchaseOrderId,
                'created_by_account_id' => $actor->getKey(),
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'subtotal_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
                'paid_kobo' => 0,
                'status' => SupplierBillStatus::Open,
                'reference_note' => $referenceNote,
                'posted_at' => now(),
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($lines as $line) {
                $description = trim(
                    (string) ($line['description'] ?? ''),
                );

                if ($description === '') {
                    throw new \InvalidArgumentException(
                        'Every supplier bill line requires a description.',
                    );
                }

                $quantityMilliunits = $this->quantity->toMilliunits(
                    (string) ($line['quantity'] ?? ''),
                );

                if ($quantityMilliunits <= 0) {
                    throw new \InvalidArgumentException(
                        'Supplier bill quantity must be greater than zero.',
                    );
                }

                $unitCostKobo = $this->money->toKobo(
                    $line['unit_cost'] ?? null,
                ) ?? 0;

                $taxBasisPoints = (int) round(
                    (float) ($line['tax_rate_percent'] ?? 0) * 100,
                );

                $lineSubtotal = (int) round(
                    ($quantityMilliunits / 1000) * $unitCostKobo,
                );

                $lineTax = (int) round(
                    $lineSubtotal * ($taxBasisPoints / 10000),
                );

                SupplierBillLine::query()->create([
                    'supplier_bill_id' => $bill->getKey(),
                    'product_id' => ! empty($line['product_id'])
                        ? (string) $line['product_id']
                        : null,
                    'description' => $description,
                    'quantity_milliunits' => $quantityMilliunits,
                    'unit_cost_kobo' => $unitCostKobo,
                    'tax_rate_basis_points' => $taxBasisPoints,
                    'line_subtotal_kobo' => $lineSubtotal,
                    'tax_kobo' => $lineTax,
                    'line_total_kobo' => $lineSubtotal + $lineTax,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $bill->forceFill([
                'subtotal_kobo' => $subtotal,
                'tax_kobo' => $taxTotal,
                'total_kobo' => $subtotal + $taxTotal,
            ])->save();

            return $bill;
        });
    }
}
