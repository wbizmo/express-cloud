<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\Accounting\FinancialPostingClassification;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Sales\SaleType;
use App\Models\AssetDepreciationPosting;
use App\Models\FinancialPosting;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\OperationRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StandaloneReceipt;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Models\SupplierReturn;
use App\Services\Operations\TransactionRetrier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class FinancialPostingCoordinator
{
    public function __construct(
        private AccountLocator $accounts,
        private JournalPoster $journals,
        private TransactionRetrier $transactions,
    ) {}

    public function sale(Sale $sale, ?OperationRequest $operation = null): FinancialPosting
    {
        $type = $sale->sale_type instanceof SaleType
            ? $sale->sale_type
            : SaleType::from((string) $sale->sale_type);

        if ($type === SaleType::Quote) {
            return $this->nonPosting(
                $sale,
                'quote',
                'quote-is-non-financial',
                $operation,
            );
        }

        if ($sale->grand_total_kobo <= 0) {
            return $this->nonPosting(
                $sale,
                'confirmed',
                'zero-value-sale',
                $operation,
            );
        }

        return $this->transactions->run(function () use ($sale, $operation): FinancialPosting {
            $cost = 0;
            /** @var SaleItem $item */
            foreach ($sale->items()->orderBy('id')->get() as $item) {
                if (! $item->track_inventory_snapshot) {
                    continue;
                }
                $cost += (int) round(
                    ($item->quantity_milliunits / 1000)
                    * $item->unit_cost_kobo_snapshot,
                );
            }

            $netRevenue = max(0, $sale->grand_total_kobo - $sale->tax_amount_kobo);
            $lines = [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('accounts_receivable')->getKey(),
                    'debit_kobo' => $sale->grand_total_kobo,
                    'customer_id' => $sale->customer_id,
                ],
                [
                    'account_id' => (string) $this->accounts
                        ->configured('sales_revenue')->getKey(),
                    'credit_kobo' => $netRevenue,
                ],
            ];

            if ($sale->tax_amount_kobo > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('output_tax')->getKey(),
                    'credit_kobo' => $sale->tax_amount_kobo,
                ];
            }

            if ($cost > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('cost_of_goods_sold')->getKey(),
                    'debit_kobo' => $cost,
                ];
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('inventory')->getKey(),
                    'credit_kobo' => $cost,
                ];
            }

            $journal = $this->journals->post(
                CarbonImmutable::parse($sale->sale_date),
                "Sale {$sale->sale_code}",
                $lines,
                $sale->branch_id,
                $sale->sold_by_account_id,
                Sale::class,
                (string) $sale->getKey(),
                'confirmed',
                $operation?->getKey(),
                $operation instanceof OperationRequest ? 1 : null,
            );

            return $this->posted($sale, 'confirmed', $journal, $operation, details: [
                'gross_kobo' => $sale->grand_total_kobo,
                'tax_kobo' => $sale->tax_amount_kobo,
                'cost_kobo' => $cost,
            ]);
        });
    }

    public function payment(Payment $payment, ?OperationRequest $operation = null): FinancialPosting
    {
        if ($payment->amount_kobo <= 0) {
            return $this->nonPosting(
                $payment,
                'received',
                'zero-value-payment',
                $operation,
            );
        }

        return $this->transactions->run(function () use ($payment, $operation): FinancialPosting {
            $sale = $payment->sale()->firstOrFail();
            $method = $payment->paymentMethod()->firstOrFail();
            $journal = $this->journals->post(
                CarbonImmutable::parse($payment->paid_at),
                "Payment for {$sale->sale_code}",
                [
                    [
                        'account_id' => $this->paymentAccountId($method),
                        'debit_kobo' => $payment->amount_kobo,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('accounts_receivable')->getKey(),
                        'credit_kobo' => $payment->amount_kobo,
                        'customer_id' => $sale->customer_id,
                    ],
                ],
                $sale->branch_id,
                $payment->recorded_by_account_id,
                Payment::class,
                (string) $payment->getKey(),
                'received',
                $operation?->getKey() ?? $payment->operation_request_id,
                $payment->operation_request_id !== null
                    ? 100 + max(1, (int) $payment->operation_sequence)
                    : null,
            );

            return $this->posted($payment, 'received', $journal, $operation, details: [
                'amount_kobo' => $payment->amount_kobo,
                'payment_method_id' => (string) $method->getKey(),
            ]);
        });
    }

    public function saleReturn(SaleReturn $return, ?OperationRequest $operation = null): FinancialPosting
    {
        if ($return->total_refund_kobo <= 0) {
            return $this->nonPosting(
                $return,
                'completed',
                'zero-value-sale-return',
                $operation,
            );
        }

        return $this->transactions->run(function () use ($return, $operation): FinancialPosting {
            $tax = 0;
            $restockCost = 0;
            /** @var SaleReturnItem $returnItem */
            foreach ($return->items()->orderBy('id')->get() as $returnItem) {
                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::query()->findOrFail($returnItem->sale_item_id);
                if ($saleItem->quantity_milliunits <= 0) {
                    continue;
                }
                $ratio = $returnItem->quantity_milliunits / $saleItem->quantity_milliunits;
                $tax += (int) round($saleItem->tax_amount_kobo * $ratio);
                if ($returnItem->restock && $saleItem->track_inventory_snapshot) {
                    $restockCost += (int) round(
                        ($returnItem->quantity_milliunits / 1000)
                        * $saleItem->unit_cost_kobo_snapshot,
                    );
                }
            }

            $tax = min($tax, $return->total_refund_kobo);
            $netReturn = $return->total_refund_kobo - $tax;
            $lines = [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('sales_returns')->getKey(),
                    'debit_kobo' => $netReturn,
                ],
                [
                    'account_id' => $this->refundCreditAccountId($return),
                    'credit_kobo' => $return->total_refund_kobo,
                    'customer_id' => $return->customer_id,
                ],
            ];

            if ($tax > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('output_tax')->getKey(),
                    'debit_kobo' => $tax,
                ];
            }

            if ($restockCost > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('inventory')->getKey(),
                    'debit_kobo' => $restockCost,
                ];
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('cost_of_goods_sold')->getKey(),
                    'credit_kobo' => $restockCost,
                ];
            }

            $journal = $this->journals->post(
                CarbonImmutable::parse($return->returned_at),
                "Sale return {$return->return_code}",
                $lines,
                $return->branch_id,
                $return->processed_by_account_id,
                SaleReturn::class,
                (string) $return->getKey(),
                'completed',
                $operation?->getKey() ?? $return->operation_request_id,
                $return->operation_request_id !== null ? 1 : null,
            );

            return $this->posted($return, 'completed', $journal, $operation, details: [
                'refund_kobo' => $return->total_refund_kobo,
                'tax_reversal_kobo' => $tax,
                'restock_cost_kobo' => $restockCost,
            ]);
        });
    }

    public function purchaseReceipt(PurchaseReceipt $receipt): FinancialPosting
    {
        return $this->simplePosting(
            $receipt,
            'recorded',
            CarbonImmutable::parse($receipt->purchased_at),
            "Purchase {$receipt->receipt_number}",
            [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('inventory')->getKey(),
                    'debit_kobo' => $receipt->total_kobo,
                ],
                [
                    'account_id' => (string) $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'credit_kobo' => $receipt->total_kobo,
                    'supplier_id' => $receipt->supplier_id,
                ],
            ],
            $receipt->branch_id,
            $receipt->recorded_by_account_id,
            $receipt->total_kobo,
        );
    }

    public function supplierBill(SupplierBill $bill): FinancialPosting
    {
        return $this->simplePosting(
            $bill,
            'posted',
            CarbonImmutable::parse($bill->bill_date),
            "Supplier bill {$bill->bill_number}",
            [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('general_expense')->getKey(),
                    'debit_kobo' => $bill->total_kobo,
                ],
                [
                    'account_id' => (string) $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'credit_kobo' => $bill->total_kobo,
                    'supplier_id' => $bill->supplier_id,
                ],
            ],
            $bill->branch_id,
            $bill->created_by_account_id,
            $bill->total_kobo,
        );
    }

    public function supplierPayment(SupplierBillPayment $payment): FinancialPosting
    {
        if ($payment->amount_kobo <= 0) {
            return $this->nonPosting($payment, 'paid', 'zero-value-supplier-payment');
        }

        return $this->transactions->run(function () use ($payment): FinancialPosting {
            $bill = $payment->supplierBill()->firstOrFail();
            $method = $payment->paymentMethod()->firstOrFail();
            $journal = $this->journals->post(
                CarbonImmutable::parse($payment->paid_at),
                "Supplier payment {$bill->bill_number}",
                [
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('accounts_payable')->getKey(),
                        'debit_kobo' => $payment->amount_kobo,
                        'supplier_id' => $bill->supplier_id,
                    ],
                    [
                        'account_id' => $this->paymentAccountId($method),
                        'credit_kobo' => $payment->amount_kobo,
                    ],
                ],
                $bill->branch_id,
                $payment->recorded_by_account_id,
                SupplierBillPayment::class,
                (string) $payment->getKey(),
                'paid',
            );

            return $this->posted($payment, 'paid', $journal, details: [
                'amount_kobo' => $payment->amount_kobo,
            ]);
        });
    }

    public function supplierReturn(SupplierReturn $return): FinancialPosting
    {
        return $this->returnToSupplierPosting(
            $return,
            'confirmed',
            CarbonImmutable::parse($return->return_date),
            "Supplier return {$return->return_number}",
            $return->total_kobo,
            $return->supplier_id,
            $return->branch_id,
            $return->created_by_account_id,
        );
    }

    public function purchaseReturn(PurchaseReturn $return): FinancialPosting
    {
        return $this->returnToSupplierPosting(
            $return,
            'completed',
            CarbonImmutable::parse($return->returned_at),
            "Purchase return {$return->return_number}",
            $return->total_kobo,
            $return->supplier_id,
            $return->branch_id,
            $return->processed_by_account_id,
        );
    }

    public function standaloneReceipt(StandaloneReceipt $receipt): FinancialPosting
    {
        if ($receipt->amount_kobo <= 0) {
            return $this->nonPosting($receipt, 'received', 'zero-value-receipt');
        }

        return $this->transactions->run(function () use ($receipt): FinancialPosting {
            /** @var PaymentMethod $method */
            $method = PaymentMethod::query()->findOrFail($receipt->payment_method_id);
            $journal = $this->journals->post(
                CarbonImmutable::parse($receipt->received_at),
                "Standalone receipt {$receipt->receipt_number}",
                [
                    [
                        'account_id' => $this->paymentAccountId($method),
                        'debit_kobo' => $receipt->amount_kobo,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('customer_deposits')->getKey(),
                        'credit_kobo' => $receipt->amount_kobo,
                        'customer_id' => $receipt->customer_id,
                    ],
                ],
                $receipt->branch_id,
                $receipt->received_by_account_id,
                StandaloneReceipt::class,
                (string) $receipt->getKey(),
                'received',
            );

            return $this->posted($receipt, 'received', $journal);
        });
    }

    public function fixedAsset(FixedAsset $asset): FinancialPosting
    {
        return $this->simplePosting(
            $asset,
            'acquired',
            CarbonImmutable::parse($asset->acquired_at),
            "Fixed asset {$asset->asset_code}",
            [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('fixed_assets')->getKey(),
                    'debit_kobo' => $asset->cost_kobo,
                ],
                [
                    'account_id' => (string) $this->accounts
                        ->configured('fixed_asset_clearing')->getKey(),
                    'credit_kobo' => $asset->cost_kobo,
                ],
            ],
            $asset->branch_id,
            $asset->created_by_account_id,
            $asset->cost_kobo,
        );
    }

    public function depreciation(
        FixedAsset $asset,
        CarbonInterface $periodEnd,
    ): FinancialPosting {
        $event = 'depreciation-'.$periodEnd->format('Y-m');
        $amount = $asset->monthlyDepreciationKobo();
        if ($amount <= 0) {
            return $this->nonPosting($asset, $event, 'zero-depreciation');
        }

        return $this->transactions->run(function () use (
            $asset,
            $periodEnd,
            $event,
            $amount,
        ): FinancialPosting {
            $journal = $this->journals->post(
                $periodEnd,
                "Depreciation {$asset->asset_code}",
                [
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('depreciation_expense')->getKey(),
                        'debit_kobo' => $amount,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('accumulated_depreciation')->getKey(),
                        'credit_kobo' => $amount,
                    ],
                ],
                $asset->branch_id,
                $asset->created_by_account_id,
                FixedAsset::class,
                (string) $asset->getKey(),
                $event,
            );

            AssetDepreciationPosting::query()->firstOrCreate(
                [
                    'fixed_asset_id' => $asset->getKey(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'journal_entry_id' => $journal->getKey(),
                    'amount_kobo' => $amount,
                ],
            );

            return $this->posted($asset, $event, $journal, details: [
                'period_end' => $periodEnd->toDateString(),
                'amount_kobo' => $amount,
            ]);
        });
    }

    public function stockMovement(StockMovement $movement): FinancialPosting
    {
        $type = $movement->movement_type instanceof StockMovementType
            ? $movement->movement_type
            : StockMovementType::from((string) $movement->movement_type);

        if ($type !== StockMovementType::Adjustment) {
            return $this->nonPosting(
                $movement,
                'movement',
                'accounted-by-originating-source',
                operationId: $movement->operation_request_id,
            );
        }

        $product = $movement->product()->firstOrFail();
        $unitCost = $movement->unit_cost_kobo ?? $product->default_cost_price_kobo;
        $value = (int) round(
            (abs($movement->quantity_delta_milliunits) / 1000) * $unitCost,
        );

        if ($value <= 0) {
            return $this->nonPosting(
                $movement,
                'movement',
                'zero-value-stock-variance',
                operationId: $movement->operation_request_id,
            );
        }

        $positive = $movement->quantity_delta_milliunits > 0;
        $journal = $this->journals->post(
            CarbonImmutable::parse($movement->occurred_at),
            'Stock variance '.$product->name,
            $positive
                ? [
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('inventory')->getKey(),
                        'debit_kobo' => $value,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('inventory_variance_gain')->getKey(),
                        'credit_kobo' => $value,
                    ],
                ]
                : [
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('inventory_variance_loss')->getKey(),
                        'debit_kobo' => $value,
                    ],
                    [
                        'account_id' => (string) $this->accounts
                            ->configured('inventory')->getKey(),
                        'credit_kobo' => $value,
                    ],
                ],
            $movement->branch_id,
            $movement->account_id,
            StockMovement::class,
            (string) $movement->getKey(),
            'movement',
            $movement->operation_request_id,
            $movement->operation_request_id !== null ? 200 : null,
        );

        return $this->posted(
            $movement,
            'movement',
            $journal,
            operationId: $movement->operation_request_id,
            details: ['value_kobo' => $value, 'reason_code' => $movement->reason_code],
        );
    }

    public function registerExistingJournal(
        JournalEntry $entry,
        string $sourceEvent = 'manual',
        ?string $reasonCode = null,
    ): FinancialPosting {
        return $this->transactions->run(function () use (
            $entry,
            $sourceEvent,
            $reasonCode,
        ): FinancialPosting {
            $this->assertBalanced($entry);

            return $this->persist(
                $entry,
                $sourceEvent,
                FinancialPostingClassification::Posted,
                $entry,
                $entry->operation_request_id,
                null,
                $reasonCode === null ? [] : ['reason_code' => $reasonCode],
            );
        });
    }

    /** @param array<string, scalar|null> $details */
    public function nonPosting(
        Model $source,
        string $sourceEvent,
        string $reasonCode,
        ?OperationRequest $operation = null,
        ?string $operationId = null,
        array $details = [],
    ): FinancialPosting {
        return $this->transactions->run(fn (): FinancialPosting => $this->persist(
            $source,
            $sourceEvent,
            FinancialPostingClassification::NonPosting,
            null,
            $operation?->getKey() ?? $operationId,
            $reasonCode,
            $details,
        ));
    }

    /**
     * @param  list<array{account_id:string,debit_kobo?:int,credit_kobo?:int,branch_id?:string|null,customer_id?:string|null,supplier_id?:string|null,description?:string|null}>  $lines
     */
    private function simplePosting(
        Model $source,
        string $event,
        CarbonInterface $date,
        string $memo,
        array $lines,
        ?string $branchId,
        ?string $actorId,
        int $amount,
    ): FinancialPosting {
        if ($amount <= 0) {
            return $this->nonPosting($source, $event, 'zero-value-source');
        }

        $journal = $this->journals->post(
            $date,
            $memo,
            $lines,
            $branchId,
            $actorId,
            $source::class,
            (string) $source->getKey(),
            $event,
        );

        return $this->posted($source, $event, $journal);
    }

    private function returnToSupplierPosting(
        Model $source,
        string $event,
        CarbonInterface $date,
        string $memo,
        int $amount,
        ?string $supplierId,
        ?string $branchId,
        ?string $actorId,
    ): FinancialPosting {
        if ($amount <= 0) {
            return $this->nonPosting($source, $event, 'zero-value-supplier-return');
        }

        $journal = $this->journals->post(
            $date,
            $memo,
            [
                [
                    'account_id' => (string) $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'debit_kobo' => $amount,
                    'supplier_id' => $supplierId,
                ],
                [
                    'account_id' => (string) $this->accounts
                        ->configured('inventory')->getKey(),
                    'credit_kobo' => $amount,
                ],
            ],
            $branchId,
            $actorId,
            $source::class,
            (string) $source->getKey(),
            $event,
        );

        return $this->posted($source, $event, $journal);
    }

    /** @param array<string, scalar|null> $details */
    private function posted(
        Model $source,
        string $sourceEvent,
        JournalEntry $journal,
        ?OperationRequest $operation = null,
        ?string $operationId = null,
        array $details = [],
    ): FinancialPosting {
        $this->assertBalanced($journal);

        return $this->persist(
            $source,
            $sourceEvent,
            FinancialPostingClassification::Posted,
            $journal,
            $operation?->getKey() ?? $operationId,
            null,
            $details,
        );
    }

    /** @param array<string, scalar|null> $details */
    private function persist(
        Model $source,
        string $sourceEvent,
        FinancialPostingClassification $classification,
        ?JournalEntry $journal,
        ?string $operationId,
        ?string $reasonCode,
        array $details,
    ): FinancialPosting {
        $sourceId = (string) $source->getKey();
        if ($sourceId === '') {
            throw new \LogicException('A financial posting source must be persisted.');
        }
        if ($classification === FinancialPostingClassification::Posted && ! $journal instanceof JournalEntry) {
            throw new \LogicException('Posted classifications require a journal entry.');
        }
        if ($classification === FinancialPostingClassification::NonPosting && $journal instanceof JournalEntry) {
            throw new \LogicException('Non-posting classifications cannot reference a journal entry.');
        }

        FinancialPosting::query()->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'source_type' => $source::class,
            'source_id' => $sourceId,
            'source_event' => $sourceEvent,
            'classification' => $classification->value,
            'journal_entry_id' => $journal?->getKey(),
            'operation_request_id' => $operationId,
            'reason_code' => $reasonCode,
            'details' => $details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR),
            'classified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var FinancialPosting $posting */
        $posting = FinancialPosting::query()
            ->where('source_type', $source::class)
            ->where('source_id', $sourceId)
            ->where('source_event', $sourceEvent)
            ->lockForUpdate()
            ->firstOrFail();

        $existingClassification = $posting->classification instanceof FinancialPostingClassification
            ? $posting->classification
            : FinancialPostingClassification::from((string) $posting->classification);
        if ($existingClassification !== $classification) {
            throw new \DomainException('Financial source classification conflict detected.');
        }
        if ((string) ($posting->journal_entry_id ?? '') !== (string) ($journal?->getKey() ?? '')) {
            throw new \DomainException('Financial source journal conflict detected.');
        }

        return $posting;
    }

    private function assertBalanced(JournalEntry $journal): void
    {
        $totals = $journal->lines()
            ->selectRaw('COALESCE(SUM(debit_kobo), 0) AS debits, COALESCE(SUM(credit_kobo), 0) AS credits')
            ->first();
        $debits = (int) ($totals?->getAttribute('debits') ?? 0);
        $credits = (int) ($totals?->getAttribute('credits') ?? 0);
        if ($debits <= 0 || $debits !== $credits) {
            throw new \DomainException('A source journal must be balanced and greater than zero.');
        }
    }

    private function paymentAccountId(PaymentMethod $method): string
    {
        if (is_string($method->ledger_account_id) && $method->ledger_account_id !== '') {
            return $method->ledger_account_id;
        }
        $name = mb_strtolower($method->name);
        $configured = str_contains($name, 'cash')
            ? 'cash'
            : ((str_contains($name, 'card') || str_contains($name, 'pos'))
                ? 'card_clearing'
                : 'bank');

        return (string) $this->accounts->configured($configured)->getKey();
    }

    private function refundCreditAccountId(SaleReturn $return): string
    {
        $method = mb_strtolower(trim((string) $return->refund_method));
        $configured = match ($method) {
            'cash' => 'cash',
            'bank transfer' => 'bank',
            'store credit' => 'customer_deposits',
            default => 'accounts_receivable',
        };

        return (string) $this->accounts->configured($configured)->getKey();
    }
}
