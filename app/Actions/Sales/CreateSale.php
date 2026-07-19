<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Enums\Inventory\StockMovementType;
use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\Organisation\BranchAccess;
use App\Services\Procurement\LowStockAlertService;
use App\Services\Sales\SaleCodeGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateSale
{
    public function __construct(
        private Quantity $quantity,
        private MoneyInput $money,
        private SaleCodeGenerator $codes,
        private LowStockAlertService $alerts,
        private BranchAccess $branchAccess, // <-- ADDED
    ) {}

    public function execute(
        StoreSaleRequest $request,
        Account $actor,
    ): Sale {
        $idempotencyKey = $request->string('idempotency_key')->trim()->toString();

        $existing = Sale::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing instanceof Sale) {
            return $existing;
        }

        $type = SaleType::from(
            $request->string('sale_type')->toString(),
        );

        return DB::transaction(function () use (
            $request,
            $actor,
            $idempotencyKey,
            $type,
        ): Sale {
            /** @var Branch $branch */
            $branch = Branch::query()->findOrFail(
                $request->string('branch_id')->toString(),
            );
            $this->branchAccess->enforce($actor, $branch); // <-- NOW WORKS

            $sale = Sale::query()->create([
                'sale_code' => $this->codes->generate($type, $branch),
                'sale_type' => $type,
                'branch_id' => $branch->getKey(),
                'customer_id' => $request->filled('customer_id')
                    ? $request->string('customer_id')->toString()
                    : null,
                'sold_by_account_id' => $actor->getKey(),
                'sale_date' => today(),
                'subtotal_kobo' => 0,
                'discount_amount_kobo' => 0,
                'tax_amount_kobo' => 0,
                'grand_total_kobo' => 0,
                'paid_amount_kobo' => 0,
                'status' => $type === SaleType::Quote
                    ? SaleStatus::Draft
                    : SaleStatus::Confirmed,
                'idempotency_key' => $idempotencyKey,
                'notes' => $request->filled('notes')
                    ? $request->string('notes')->trim()->toString()
                    : null,
                'credit_note' => $request->filled('credit_note')
                    ? $request->string('credit_note')->trim()->toString()
                    : null,
                'confirmed_at' => $type === SaleType::Quote
                    ? null
                    : now(),
            ]);

            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;

            foreach ($request->array('items') as $item) {
                if (! is_array($item)) {
                    continue;
                }

                /** @var Product $product */
                $product = Product::query()->findOrFail(
                    (string) ($item['product_id'] ?? ''),
                );

                $quantityMilliunits = $this->quantity->toMilliunits(
                    (string) ($item['quantity'] ?? ''),
                );

                if ($quantityMilliunits <= 0) {
                    throw new \InvalidArgumentException(
                        'Sale quantity must be greater than zero.',
                    );
                }

                $unitPriceKobo = $this->money->toKobo(
                    $item['unit_price'] ?? null,
                ) ?? (int) ($product->branchPrices()->where('branch_id', $branch->getKey())->value('price_kobo') ?? $product->default_price_kobo);

                $lineSubtotal = (int) round(
                    ($quantityMilliunits / 1000) * $unitPriceKobo,
                );
                $discountKobo = min(
                    $lineSubtotal,
                    $this->money->toKobo(
                        $item['discount'] ?? null,
                    ) ?? 0,
                );

                $taxBasisPoints = $product->taxRate()
                    ->value('rate_basis_points');
                $taxBasisPoints = is_numeric($taxBasisPoints)
                    ? (int) $taxBasisPoints
                    : 0;

                $taxable = $lineSubtotal - $discountKobo;
                $taxKobo = (int) round(
                    $taxable * ($taxBasisPoints / 10000),
                );
                $lineTotal = $taxable + $taxKobo;

                SaleItem::query()->create([
                    'sale_id' => $sale->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'track_inventory_snapshot' => $product->track_inventory,
                    'quantity_milliunits' => $quantityMilliunits,
                    'unit_price_kobo' => $unitPriceKobo,
                    'unit_cost_kobo_snapshot' => $product->default_cost_price_kobo,
                    'discount_amount_kobo' => $discountKobo,
                    'tax_amount_kobo' => $taxKobo,
                    'line_total_kobo' => $lineTotal,
                ]);

                if ($type->movesStock() && $product->track_inventory) {
                    $available = (int) ($product->branchStock()->where('branch_id', $branch->getKey())->value('quantity_milliunits') ?? 0);
                    if (! (bool) $branch->allow_zero_stock_sales && $quantityMilliunits > $available) {
                        throw new \DomainException('Insufficient branch stock for '.$product->name.'.');
                    }

                    $this->deductStock(
                        $product,
                        $branch,
                        $actor,
                        $sale,
                        $quantityMilliunits,
                    );
                }

                $subtotal += $lineSubtotal;
                $discountTotal += $discountKobo;
                $taxTotal += $taxKobo;
            }

            $grandTotal = $subtotal - $discountTotal + $taxTotal;
            $paidTotal = 0;

            if ($type !== SaleType::Quote) {
                foreach ($request->array('payments') as $payment) {
                    if (! is_array($payment)) {
                        continue;
                    }

                    /** @var PaymentMethod $method */
                    $method = PaymentMethod::query()
                        ->where('is_active', true)
                        ->findOrFail(
                            (string) (
                                $payment['payment_method_id'] ?? ''
                            ),
                        );

                    $amountKobo = $this->money->toKobo(
                        $payment['amount'] ?? null,
                    ) ?? 0;

                    if ($amountKobo <= 0) {
                        continue;
                    }

                    Payment::query()->create([
                        'sale_id' => $sale->getKey(),
                        'payment_method_id' => $method->getKey(),
                        'amount_kobo' => $amountKobo,
                        'recorded_by_account_id' => $actor->getKey(),
                        'reference' => isset($payment['reference'])
                            ? trim((string) $payment['reference'])
                            : null,
                        'paid_at' => now(),
                    ]);

                    $paidTotal += $amountKobo;
                }
            }

            $sale->forceFill([
                'subtotal_kobo' => $subtotal,
                'discount_amount_kobo' => $discountTotal,
                'tax_amount_kobo' => $taxTotal,
                'grand_total_kobo' => $grandTotal,
                'paid_amount_kobo' => min($paidTotal, $grandTotal),
                'status' => $type === SaleType::Quote
                    ? SaleStatus::Draft
                    : SaleStatus::fromPayment(
                        $paidTotal,
                        $grandTotal,
                    ),
            ])->save();

            return $sale;
        }, 3);
    }

    private function deductStock(
        Product $product,
        Branch $branch,
        Account $actor,
        Sale $sale,
        int $quantityMilliunits,
    ): void {
        $stock = ProductBranchStock::query()
            ->where('product_id', $product->getKey())
            ->where('branch_id', $branch->getKey())
            ->lockForUpdate()
            ->first();

        if (! $stock instanceof ProductBranchStock) {
            throw new \DomainException(
                'No stock balance exists for this product at the selected branch.',
            );
        }

        $newBalance = $stock->quantity_milliunits
            - $quantityMilliunits;

        if ($newBalance < 0) {
            throw new \DomainException(
                sprintf(
                    'Not enough stock at %s — %s available.',
                    $branch->name,
                    $this->quantity->format(
                        $stock->quantity_milliunits,
                    ),
                ),
            );
        }

        $stock->forceFill([
            'quantity_milliunits' => $newBalance,
            'last_movement_at' => now(),
        ])->save();

        $this->alerts->refresh($stock);

        StockMovement::query()->create([
            'product_id' => $product->getKey(),
            'branch_id' => $branch->getKey(),
            'account_id' => $actor->getKey(),
            'movement_type' => StockMovementType::Sale,
            'quantity_delta_milliunits' => -$quantityMilliunits,
            'balance_after_milliunits' => $newBalance,
            'reference_type' => 'sale',
            'reference_id' => $sale->getKey(),
            'note' => 'Stock deducted by '.$sale->sale_code,
            'occurred_at' => now(),
        ]);
    }
}