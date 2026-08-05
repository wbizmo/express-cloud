<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\BranchAccess;
use App\Services\Sales\SaleCodeGenerator;
use Illuminate\Database\Eloquent\Collection;

final readonly class CreateSale
{
    public function __construct(
        private Quantity $quantity,
        private MoneyInput $money,
        private SaleCodeGenerator $codes,
        private BranchAccess $branchAccess,
        private StockLedger $stock,
        private CommandBoundary $commands,
        private FinancialPostingCoordinator $postings,
    ) {}

    public function execute(
        StoreSaleRequest $request,
        Account $actor,
    ): Sale {
        $idempotencyKey = $request->string('idempotency_key')
            ->trim()
            ->toString();
        $branchId = $request->string('branch_id')->toString();

        $result = $this->commands->execute(
            'sale.create',
            $idempotencyKey,
            $request->validated(),
            $actor,
            $branchId,
            function (OperationRequest $operation) use (
                $request,
                $actor,
                $idempotencyKey,
                $branchId,
            ): Sale {
                /** @var Branch $branch */
                $branch = Branch::query()
                    ->whereKey($branchId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->branchAccess->enforce($actor, $branch);

                $type = SaleType::from(
                    $request->string('sale_type')->toString(),
                );
                $items = $this->normalizedItems($request->array('items'));
                $products = $this->lockedProducts($items);
                $lineData = [];
                $subtotal = 0;
                $discountTotal = 0;
                $taxTotal = 0;

                foreach ($items as $item) {
                    $productId = (string) $item['product_id'];
                    $product = $products->get($productId);

                    if (! $product instanceof Product) {
                        throw new \DomainException(
                            'A selected sale product no longer exists.',
                        );
                    }

                    $quantityMilliunits = $this->quantity->toMilliunits(
                        (string) $item['quantity'],
                    );

                    if ($quantityMilliunits <= 0) {
                        throw new \InvalidArgumentException(
                            'Sale quantity must be greater than zero.',
                        );
                    }

                    $unitPriceKobo = $this->money->toKobo(
                        $item['unit_price'] ?? null,
                    ) ?? (int) (
                        $product->branchPrices()
                            ->where('branch_id', $branch->getKey())
                            ->value('price_kobo')
                        ?? $product->default_price_kobo
                    );
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

                    $lineData[] = [
                        'product' => $product,
                        'quantity_milliunits' => $quantityMilliunits,
                        'unit_price_kobo' => $unitPriceKobo,
                        'discount_amount_kobo' => $discountKobo,
                        'tax_amount_kobo' => $taxKobo,
                        'line_total_kobo' => $lineTotal,
                    ];
                    $subtotal += $lineSubtotal;
                    $discountTotal += $discountKobo;
                    $taxTotal += $taxKobo;
                }

                $grandTotal = $subtotal - $discountTotal + $taxTotal;
                $paymentData = $type === SaleType::Quote
                    ? []
                    : $this->normalizedPayments(
                        $request->array('payments'),
                    );
                $paidTotal = array_sum(array_column(
                    $paymentData,
                    'amount_kobo',
                ));

                if ($paidTotal > $grandTotal) {
                    throw new \DomainException(
                        'Payment amount exceeds the sale total. Overpayments are not permitted.',
                    );
                }

                $sale = Sale::query()->create([
                    'sale_code' => $this->codes->generate($type, $branch),
                    'sale_type' => $type,
                    'branch_id' => $branch->getKey(),
                    'customer_id' => $request->filled('customer_id')
                        ? $request->string('customer_id')->toString()
                        : null,
                    'sold_by_account_id' => $actor->getKey(),
                    'sale_date' => today(),
                    'subtotal_kobo' => $subtotal,
                    'discount_amount_kobo' => $discountTotal,
                    'tax_amount_kobo' => $taxTotal,
                    'grand_total_kobo' => $grandTotal,
                    'paid_amount_kobo' => $paidTotal,
                    'status' => $type === SaleType::Quote
                        ? SaleStatus::Draft
                        : SaleStatus::fromPayment(
                            $paidTotal,
                            $grandTotal,
                        ),
                    'idempotency_key' => $idempotencyKey,
                    'operation_request_id' => $operation->getKey(),
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

                foreach ($lineData as $index => $line) {
                    /** @var Product $product */
                    $product = $line['product'];

                    SaleItem::query()->create([
                        'sale_id' => $sale->getKey(),
                        'product_id' => $product->getKey(),
                        'product_name_snapshot' => $product->name,
                        'sku_snapshot' => $product->sku,
                        'track_inventory_snapshot' => $product->track_inventory,
                        'quantity_milliunits' => $line['quantity_milliunits'],
                        'unit_price_kobo' => $line['unit_price_kobo'],
                        'unit_cost_kobo_snapshot' => $product->default_cost_price_kobo,
                        'discount_amount_kobo' => $line['discount_amount_kobo'],
                        'tax_amount_kobo' => $line['tax_amount_kobo'],
                        'line_total_kobo' => $line['line_total_kobo'],
                    ]);

                    if ($type->movesStock() && $product->track_inventory) {
                        $this->stock->sale(
                            $product,
                            $branch,
                            $actor,
                            (int) $line['quantity_milliunits'],
                            (string) $sale->getKey(),
                            'Stock deducted by '.$sale->sale_code,
                            $operation,
                            $index + 1,
                        );
                    }
                }

                foreach ($paymentData as $index => $payment) {
                    Payment::query()->create([
                        'sale_id' => $sale->getKey(),
                        'payment_method_id' => $payment['method']->getKey(),
                        'amount_kobo' => $payment['amount_kobo'],
                        'recorded_by_account_id' => $actor->getKey(),
                        'reference' => $payment['reference'],
                        'operation_request_id' => $operation->getKey(),
                        'operation_sequence' => $index + 1,
                        'paid_at' => now(),
                    ]);
                }

                $this->postings->sale($sale, $operation);

                return $sale;
            },
        );

        if (! $result instanceof Sale) {
            throw new \LogicException('The sale command returned an invalid result.');
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizedItems(array $items): array
    {
        $normalized = array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item),
        ));

        usort(
            $normalized,
            static fn (array $left, array $right): int => strcmp(
                (string) ($left['product_id'] ?? ''),
                (string) ($right['product_id'] ?? ''),
            ),
        );

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Collection<string, Product>
     */
    private function lockedProducts(array $items): Collection
    {
        $ids = collect($items)
            ->pluck('product_id')
            ->filter(static fn (mixed $id): bool => is_string($id))
            ->unique()
            ->sort()
            ->values();

        /** @var Collection<string, Product> $products */
        $products = Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (Product $product): string => (string) $product->getKey());

        return $products;
    }

    /**
     * @param  array<int, mixed>  $payments
     * @return list<array{method: PaymentMethod, amount_kobo: int, reference: ?string}>
     */
    private function normalizedPayments(array $payments): array
    {
        $payments = array_values(array_filter(
            $payments,
            static fn (mixed $payment): bool => is_array($payment),
        ));
        usort(
            $payments,
            static fn (array $left, array $right): int => strcmp(
                (string) ($left['payment_method_id'] ?? ''),
                (string) ($right['payment_method_id'] ?? ''),
            ),
        );
        $normalized = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                continue;
            }

            $amountKobo = $this->money->toKobo(
                $payment['amount'] ?? null,
            ) ?? 0;

            if ($amountKobo <= 0) {
                continue;
            }

            /** @var PaymentMethod $method */
            $method = PaymentMethod::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail((string) ($payment['payment_method_id'] ?? ''));

            $reference = isset($payment['reference'])
                ? trim((string) $payment['reference'])
                : null;

            $normalized[] = [
                'method' => $method,
                'amount_kobo' => $amountKobo,
                'reference' => $reference === '' ? null : $reference,
            ];
        }

        return $normalized;
    }
}
