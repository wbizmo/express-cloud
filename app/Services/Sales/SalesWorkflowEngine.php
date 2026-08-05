<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Actions\Sales\CreateSale;
use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryLine;
use App\Models\SalesDocumentEvent;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\CommandBoundary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SalesWorkflowEngine
{
    public function __construct(
        private CreateSale $create,
        private CommandBoundary $commands,
        private SaleCodeGenerator $codes,
        private StockLedger $stock,
        private FinancialPostingCoordinator $postings,
    ) {}

    public function create(StoreSaleRequest $request, Account $actor): Sale
    {
        return $this->create->execute($request, $actor);
    }

    public function convert(
        Sale $source,
        SaleType $target,
        Account $actor,
        string $idempotencyKey,
        string $memo,
    ): Sale {
        if (! in_array($target, [SaleType::Order, SaleType::Invoice], true)) {
            throw new \DomainException('Quotes may only become orders or invoices.');
        }

        $sourceType = $source->sale_type instanceof SaleType
            ? $source->sale_type
            : SaleType::from((string) $source->sale_type);
        if (
            ! ($sourceType === SaleType::Quote
                || ($sourceType === SaleType::Order && $target === SaleType::Invoice))
        ) {
            throw new \DomainException('The requested sales-document conversion is not permitted.');
        }
        if (trim($memo) === '') {
            throw new \DomainException('A conversion memo is required.');
        }

        $result = $this->commands->execute(
            'sales.document.convert',
            $idempotencyKey,
            [
                'source_id' => (string) $source->getKey(),
                'target_type' => $target->value,
                'memo' => trim($memo),
            ],
            $actor,
            (string) $source->branch_id,
            function (OperationRequest $operation) use ($source, $target, $actor, $memo): Sale {
                /** @var Sale $locked */
                $locked = Sale::query()
                    ->whereKey($source->getKey())
                    ->with('items')
                    ->lockForUpdate()
                    ->firstOrFail();
                /** @var Branch $branch */
                $branch = Branch::query()
                    ->whereKey($locked->branch_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $items = $locked->items->sortBy('product_id')->values();
                $products = $this->lockedProducts($items);

                $sale = Sale::query()->create([
                    'sale_code' => $this->codes->generate($target, $branch),
                    'sale_type' => $target,
                    'branch_id' => $locked->branch_id,
                    'customer_id' => $locked->customer_id,
                    'sold_by_account_id' => $actor->getKey(),
                    'converted_from_sale_id' => $locked->getKey(),
                    'sale_date' => today(),
                    'subtotal_kobo' => $locked->subtotal_kobo,
                    'discount_amount_kobo' => $locked->discount_amount_kobo,
                    'tax_amount_kobo' => $locked->tax_amount_kobo,
                    'grand_total_kobo' => $locked->grand_total_kobo,
                    'paid_amount_kobo' => 0,
                    'status' => $target === SaleType::Order
                        ? SaleStatus::Draft
                        : SaleStatus::Confirmed,
                    'idempotency_key' => 'conversion-'.(string) $operation->getKey(),
                    'operation_request_id' => $operation->getKey(),
                    'notes' => $locked->notes,
                    'credit_note' => $locked->credit_note,
                    'workflow_state' => $target === SaleType::Order ? 'approved' : 'confirmed',
                    'due_date' => $locked->due_date,
                    'payment_terms_days' => $locked->payment_terms_days,
                    'rounding_adjustment_kobo' => $locked->rounding_adjustment_kobo,
                    'fulfilment_status' => $target === SaleType::Order ? 'pending' : 'not_required',
                    'document_version' => 1,
                    'approval_memo' => trim($memo),
                    'confirmed_at' => $target === SaleType::Invoice ? now() : null,
                ]);

                /** @var SaleItem $item */
                foreach ($items as $index => $item) {
                    SaleItem::query()->create([
                        'sale_id' => $sale->getKey(),
                        'product_id' => $item->product_id,
                        'product_name_snapshot' => $item->product_name_snapshot,
                        'sku_snapshot' => $item->sku_snapshot,
                        'track_inventory_snapshot' => $item->track_inventory_snapshot,
                        'quantity_milliunits' => $item->quantity_milliunits,
                        'unit_price_kobo' => $item->unit_price_kobo,
                        'unit_cost_kobo_snapshot' => $item->unit_cost_kobo_snapshot,
                        'discount_amount_kobo' => $item->discount_amount_kobo,
                        'tax_amount_kobo' => $item->tax_amount_kobo,
                        'line_total_kobo' => $item->line_total_kobo,
                    ]);

                    $product = $products->get((string) $item->product_id);
                    if ($target->movesStock() && $item->track_inventory_snapshot) {
                        if (! $product instanceof Product) {
                            throw new \DomainException('A converted sale product no longer exists.');
                        }
                        $this->stock->sale(
                            $product,
                            $branch,
                            $actor,
                            (int) $item->quantity_milliunits,
                            (string) $sale->getKey(),
                            'Stock deducted by converted document '.$sale->sale_code,
                            $operation,
                            $index + 1,
                        );
                    }
                }

                $locked->forceFill([
                    'workflow_state' => 'converted',
                    'document_version' => (int) $locked->document_version + 1,
                ])->save();
                $this->event($locked, $actor, 'converted', 'converted', $memo, [
                    'converted_to_id' => (string) $sale->getKey(),
                    'target_type' => $target->value,
                ]);
                $this->event($sale, $actor, 'created_from_document', $sale->workflow_state, $memo, [
                    'source_id' => (string) $locked->getKey(),
                ]);
                $this->postings->sale($sale, $operation);

                return $sale;
            },
        );

        if (! $result instanceof Sale) {
            throw new \LogicException('The sales conversion returned an invalid result.');
        }

        return $result;
    }

    /** @param list<array{sale_item_id: string, quantity_milliunits: int}> $lines */
    public function deliver(
        Sale $sale,
        Account $actor,
        array $lines,
        ?string $warehouseId,
        string $memo,
    ): SalesDelivery {
        if (trim($memo) === '') {
            throw new \DomainException('A delivery memo is required.');
        }

        return DB::transaction(function () use ($sale, $actor, $lines, $warehouseId, $memo): SalesDelivery {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($locked->workflow_state, ['confirmed', 'approved'], true)) {
                throw new \DomainException('Only confirmed or approved sales documents may be fulfilled.');
            }

            $delivery = SalesDelivery::query()->create([
                'delivery_number' => 'DEL-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'sale_id' => $locked->getKey(),
                'warehouse_id' => $warehouseId,
                'delivered_by_account_id' => $actor->getKey(),
                'status' => 'dispatched',
                'delivery_address' => $locked->customer?->shipping_address,
                'notes' => trim($memo),
                'dispatched_at' => now(),
            ]);

            foreach ($lines as $line) {
                $item = SaleItem::query()
                    ->where('sale_id', $locked->getKey())
                    ->whereKey($line['sale_item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $quantity = (int) $line['quantity_milliunits'];
                if ($quantity <= 0 || $quantity > $item->quantity_milliunits) {
                    throw new \DomainException('Delivery quantity exceeds the sale line quantity.');
                }
                SalesDeliveryLine::query()->create([
                    'sales_delivery_id' => $delivery->getKey(),
                    'sale_item_id' => $item->getKey(),
                    'quantity_milliunits' => $quantity,
                ]);
            }

            $locked->forceFill([
                'fulfilment_status' => 'dispatched',
                'document_version' => (int) $locked->document_version + 1,
            ])->save();
            $this->event($locked, $actor, 'delivery_dispatched', $locked->workflow_state, $memo, [
                'delivery_id' => (string) $delivery->getKey(),
            ]);

            return $delivery;
        }, 3);
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @return Collection<string, Product>
     */
    private function lockedProducts(Collection $items): Collection
    {
        /** @var Collection<string, Product> $products */
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->unique()->sort()->values())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (Product $product): string => (string) $product->getKey());

        return $products;
    }

    /** @param array<string, mixed> $details */
    private function event(
        Sale $sale,
        Account $actor,
        string $eventType,
        string $toState,
        string $memo,
        array $details,
    ): void {
        SalesDocumentEvent::query()->create([
            'sale_id' => $sale->getKey(),
            'account_id' => $actor->getKey(),
            'event_type' => $eventType,
            'from_state' => null,
            'to_state' => $toState,
            'details' => $details,
            'memo' => trim($memo),
            'occurred_at' => now(),
        ]);
    }
}
