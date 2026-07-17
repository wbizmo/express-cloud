<?php

declare(strict_types=1);

namespace App\Actions\Commercial;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\Supplier;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class RecordPurchaseReceipt
{
    public function __construct(
        private Quantity $quantity,
        private MoneyInput $money,
        private StockLedger $ledger,
        private AuditLogger $audit,
    ) {}

    /**
     * @param list<array{
     *   product_id:string,
     *   quantity:string,
     *   unit_cost:mixed,
     *   discount?:mixed,
     *   tax?:mixed
     * }> $lines
     */
    public function execute(
        Request $request,
        Supplier $supplier,
        Branch $branch,
        Account $actor,
        array $lines,
        string $purchasedAt,
        ?string $reference,
        ?string $notes,
    ): PurchaseReceipt {
        return DB::transaction(function () use (
            $request,
            $supplier,
            $branch,
            $actor,
            $lines,
            $purchasedAt,
            $reference,
            $notes,
        ): PurchaseReceipt {
            $receipt = PurchaseReceipt::query()->create([
                'receipt_number' => 'PUR-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(6)),
                'supplier_id' => $supplier->getKey(),
                'branch_id' => $branch->getKey(),
                'recorded_by_account_id' => $actor->getKey(),
                'purchased_at' => $purchasedAt,
                'supplier_reference' => $reference,
                'subtotal_kobo' => 0,
                'discount_kobo' => 0,
                'tax_kobo' => 0,
                'total_kobo' => 0,
                'status' => 'recorded',
                'notes' => $notes,
            ]);

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = Product::query()->findOrFail(
                    $line['product_id'],
                );

                $quantity = $this->quantity->toMilliunits(
                    $line['quantity'],
                );
                $unitCost = $this->money->toKobo(
                    $line['unit_cost'],
                ) ?? 0;
                $lineSubtotal = (int) round(
                    ($quantity / 1000) * $unitCost,
                );
                $lineDiscount = min(
                    $lineSubtotal,
                    $this->money->toKobo(
                        $line['discount'] ?? null,
                    ) ?? 0,
                );
                $lineTax = $this->money->toKobo(
                    $line['tax'] ?? null,
                ) ?? 0;
                $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;

                PurchaseReceiptLine::query()->create([
                    'purchase_receipt_id' => $receipt->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity_milliunits' => $quantity,
                    'unit_cost_kobo' => $unitCost,
                    'discount_kobo' => $lineDiscount,
                    'tax_kobo' => $lineTax,
                    'line_total_kobo' => $lineTotal,
                ]);

                if ($product->track_inventory) {
                    $this->ledger->intake(
                        $product,
                        $branch,
                        $actor,
                        $quantity,
                        $unitCost,
                        'purchase_receipt',
                        (string) $receipt->getKey(),
                        $reference,
                    );
                }

                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;
            }

            $receipt->forceFill([
                'subtotal_kobo' => $subtotal,
                'discount_kobo' => $discount,
                'tax_kobo' => $tax,
                'total_kobo' => $subtotal - $discount + $tax,
            ])->save();

            $this->audit->record(
                $request,
                'purchase.recorded',
                'purchase_receipt',
                $receipt,
                after: [
                    'supplier_id' => $supplier->getKey(),
                    'branch_id' => $branch->getKey(),
                    'total_kobo' => $receipt->total_kobo,
                ],
            );

            return $receipt->load('lines');
        }, 3);
    }
}
