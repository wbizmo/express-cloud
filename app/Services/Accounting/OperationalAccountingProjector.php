<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\FixedAsset;
use App\Models\Payment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use Carbon\CarbonImmutable;

final readonly class OperationalAccountingProjector
{
    public function __construct(
        private AccountLocator $accounts,
        private JournalPoster $journals,
    ) {}

    public function sale(Sale $sale): void
    {
        if ((string) $sale->sale_type === 'quote') {
            return;
        }

        $netRevenue = max(
            0,
            $sale->grand_total_kobo - $sale->tax_amount_kobo,
        );
        $cost = (int) $sale->items()
            ->selectRaw(
                'COALESCE(SUM((quantity_milliunits / 1000) '
                .' * unit_cost_kobo_snapshot), 0) AS total_cost',
            )
            ->value('total_cost');

        $lines = [
            [
                'account_id' => $this->accounts
                    ->configured('accounts_receivable')->getKey(),
                'debit_kobo' => $sale->grand_total_kobo,
                'customer_id' => $sale->customer_id,
            ],
            [
                'account_id' => $this->accounts
                    ->configured('sales_revenue')->getKey(),
                'credit_kobo' => $netRevenue,
            ],
        ];

        if ($sale->tax_amount_kobo > 0) {
            $lines[] = [
                'account_id' => $this->accounts
                    ->configured('output_tax')->getKey(),
                'credit_kobo' => $sale->tax_amount_kobo,
            ];
        }

        if ($cost > 0) {
            $lines[] = [
                'account_id' => $this->accounts
                    ->configured('cost_of_goods_sold')->getKey(),
                'debit_kobo' => $cost,
            ];
            $lines[] = [
                'account_id' => $this->accounts
                    ->configured('inventory')->getKey(),
                'credit_kobo' => $cost,
            ];
        }

        $this->journals->post(
            CarbonImmutable::parse($sale->sale_date),
            "Sale {$sale->sale_code}",
            $lines,
            $sale->branch_id,
            $sale->sold_by_account_id,
            Sale::class,
            (string) $sale->getKey(),
            'confirmed',
        );
    }

    public function payment(Payment $payment): void
    {
        $sale = $payment->sale()->firstOrFail();
        $method = $payment->paymentMethod()->firstOrFail();
        $accountId = $method->ledger_account_id
            ?? $this->legacyAccountIdForMethodName($method->name);

        $this->journals->post(
            CarbonImmutable::parse($payment->paid_at),
            "Payment for {$sale->sale_code}",
            [
                [
                    'account_id' => $accountId,
                    'debit_kobo' => $payment->amount_kobo,
                ],
                [
                    'account_id' => $this->accounts
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
        );
    }

    /**
     * Legacy fallback for payment methods created before the explicit
     * ledger_account_id link existed. New and edited payment methods should
     * always carry an explicit link (see PaymentMethodController), so this
     * path only serves old, never-relinked rows.
     */
    private function legacyAccountIdForMethodName(string $name): string
    {
        $accountName = str_contains(mb_strtolower($name), 'cash')
            ? 'cash'
            : (str_contains(mb_strtolower($name), 'card')
                || str_contains(mb_strtolower($name), 'pos')
                    ? 'card_clearing'
                    : 'bank');

        return $this->accounts->configured($accountName)->getKey();
    }

    public function purchase(PurchaseReceipt $purchase): void
    {
        $this->journals->post(
            CarbonImmutable::parse($purchase->purchased_at),
            "Purchase {$purchase->receipt_number}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('inventory')->getKey(),
                    'debit_kobo' => $purchase->total_kobo,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'credit_kobo' => $purchase->total_kobo,
                    'supplier_id' => $purchase->supplier_id,
                ],
            ],
            $purchase->branch_id,
            $purchase->recorded_by_account_id,
            PurchaseReceipt::class,
            (string) $purchase->getKey(),
            'recorded',
        );
    }

    public function supplierBill(SupplierBill $bill): void
    {
        $this->journals->post(
            CarbonImmutable::parse($bill->bill_date),
            "Supplier bill {$bill->bill_number}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('general_expense')->getKey(),
                    'debit_kobo' => $bill->total_kobo,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'credit_kobo' => $bill->total_kobo,
                    'supplier_id' => $bill->supplier_id,
                ],
            ],
            $bill->branch_id,
            $bill->created_by_account_id,
            SupplierBill::class,
            (string) $bill->getKey(),
            'posted',
        );
    }

    public function supplierPayment(SupplierBillPayment $payment): void
    {
        $bill = $payment->supplierBill()->firstOrFail();

        $this->journals->post(
            CarbonImmutable::parse($payment->paid_at),
            "Supplier payment {$bill->bill_number}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'debit_kobo' => $payment->amount_kobo,
                    'supplier_id' => $bill->supplier_id,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('bank')->getKey(),
                    'credit_kobo' => $payment->amount_kobo,
                ],
            ],
            $bill->branch_id,
            $payment->recorded_by_account_id,
            SupplierBillPayment::class,
            (string) $payment->getKey(),
            'paid',
        );
    }

    public function saleReturn(SaleReturn $return): void
    {
        $this->journals->post(
            CarbonImmutable::parse($return->returned_at),
            "Sale return {$return->return_code}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('sales_returns')->getKey(),
                    'debit_kobo' => $return->total_refund_kobo,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('accounts_receivable')->getKey(),
                    'credit_kobo' => $return->total_refund_kobo,
                    'customer_id' => $return->customer_id,
                ],
            ],
            $return->branch_id,
            $return->processed_by_account_id,
            SaleReturn::class,
            (string) $return->getKey(),
            'completed',
        );
    }

    public function purchaseReturn(PurchaseReturn $return): void
    {
        $this->journals->post(
            CarbonImmutable::parse($return->returned_at),
            "Purchase return {$return->return_number}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('accounts_payable')->getKey(),
                    'debit_kobo' => $return->total_kobo,
                    'supplier_id' => $return->supplier_id,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('inventory')->getKey(),
                    'credit_kobo' => $return->total_kobo,
                ],
            ],
            $return->branch_id,
            $return->processed_by_account_id,
            PurchaseReturn::class,
            (string) $return->getKey(),
            'completed',
        );
    }

    public function standaloneReceipt(StandaloneReceipt $receipt): void
    {
        $this->journals->post(
            CarbonImmutable::parse($receipt->received_at),
            "Standalone receipt {$receipt->receipt_number}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('cash')->getKey(),
                    'debit_kobo' => $receipt->amount_kobo,
                ],
                [
                    'account_id' => $this->accounts
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
    }

    public function fixedAsset(FixedAsset $asset): void
    {
        $this->journals->post(
            CarbonImmutable::parse($asset->acquired_at),
            "Fixed asset {$asset->asset_code}",
            [
                [
                    'account_id' => $this->accounts
                        ->configured('fixed_assets')->getKey(),
                    'debit_kobo' => $asset->cost_kobo,
                ],
                [
                    'account_id' => $this->accounts
                        ->configured('fixed_asset_clearing')->getKey(),
                    'credit_kobo' => $asset->cost_kobo,
                ],
            ],
            $asset->branch_id,
            $asset->created_by_account_id,
            FixedAsset::class,
            (string) $asset->getKey(),
            'acquired',
        );
    }
}
