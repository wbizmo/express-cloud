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

/**
 * Backward-compatible adapter retained for integrations that still resolve the
 * former projector. New writes call FinancialPostingCoordinator immediately.
 */
final readonly class OperationalAccountingProjector
{
    public function __construct(private FinancialPostingCoordinator $postings) {}

    public function sale(Sale $sale): void
    {
        $this->postings->sale($sale);
    }

    public function payment(Payment $payment): void
    {
        $this->postings->payment($payment);
    }

    public function purchase(PurchaseReceipt $purchase): void
    {
        $this->postings->purchaseReceipt($purchase);
    }

    public function supplierBill(SupplierBill $bill): void
    {
        $this->postings->supplierBill($bill);
    }

    public function supplierPayment(SupplierBillPayment $payment): void
    {
        $this->postings->supplierPayment($payment);
    }

    public function saleReturn(SaleReturn $return): void
    {
        $this->postings->saleReturn($return);
    }

    public function purchaseReturn(PurchaseReturn $return): void
    {
        $this->postings->purchaseReturn($return);
    }

    public function standaloneReceipt(StandaloneReceipt $receipt): void
    {
        $this->postings->standaloneReceipt($receipt);
    }

    public function fixedAsset(FixedAsset $asset): void
    {
        $this->postings->fixedAsset($asset);
    }
}
