<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\FixedAsset;
use App\Models\Payment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Models\SupplierReturn;
use App\Services\Accounting\FinancialPostingCoordinator;
use Illuminate\Database\Eloquent\Model;

final readonly class FinancialSourceObserver
{
    public function __construct(private FinancialPostingCoordinator $postings) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Payment => $this->postings->payment($model),
            $model instanceof SupplierBillPayment => $this->postings->supplierPayment($model),
            $model instanceof StandaloneReceipt => $this->postings->standaloneReceipt($model),
            $model instanceof FixedAsset => $this->postings->fixedAsset($model),
            $model instanceof StockMovement => $this->postings->stockMovement($model),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof PurchaseReceipt && $model->total_kobo > 0 => $this->postings->purchaseReceipt($model),
            $model instanceof SupplierBill
                && $model->total_kobo > 0
                && $model->posted_at !== null => $this->postings->supplierBill($model),
            $model instanceof SaleReturn && $model->total_refund_kobo > 0 => $this->postings->saleReturn($model),
            $model instanceof PurchaseReturn && $model->total_kobo > 0 => $this->postings->purchaseReturn($model),
            $model instanceof SupplierReturn && $model->total_kobo > 0 => $this->postings->supplierReturn($model),
            default => null,
        };
    }
}
