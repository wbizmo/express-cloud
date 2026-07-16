<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Procurement\PurchaseOrderStatus;
use App\Http\Requests\Admin\Procurement\StorePurchaseOrderRequest;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreatePurchaseOrder
{
    public function __construct(
        private Quantity $quantity,
        private MoneyInput $money,
    ) {}

    public function execute(
        StorePurchaseOrderRequest $request,
        Account $actor,
    ): PurchaseOrder {
        return DB::transaction(function () use (
            $request,
            $actor,
        ): PurchaseOrder {
            $subtotal = 0;
            $tax = 0;

            $order = PurchaseOrder::query()->create([
                'order_number' => 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'supplier_id' => $request->string('supplier_id')->toString(),
                'branch_id' => $request->string('branch_id')->toString(),
                'created_by_account_id' => $actor->getKey(),
                'status' => PurchaseOrderStatus::Draft,
                'expected_at' => $request->date('expected_at'),
                'reference_note' => $request->string('reference_note')->trim()->toString(),
                'subtotal_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
            ]);

            foreach ($request->array('lines') as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $quantityMilliunits = $this->quantity->toMilliunits(
                    (string) ($line['quantity'] ?? ''),
                );
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

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->getKey(),
                    'product_id' => (string) ($line['product_id'] ?? ''),
                    'ordered_quantity_milliunits' => $quantityMilliunits,
                    'received_quantity_milliunits' => 0,
                    'unit_cost_kobo' => $unitCostKobo,
                    'tax_rate_basis_points' => $taxBasisPoints,
                    'line_total_kobo' => $lineSubtotal + $lineTax,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $order->forceFill([
                'subtotal_kobo' => $subtotal,
                'tax_kobo' => $tax,
                'total_kobo' => $subtotal + $tax,
            ])->save();

            return $order;
        });
    }
}
