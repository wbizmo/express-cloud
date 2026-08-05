<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\Accounting\FinancialPostingClassification;
use App\Models\AssetDepreciationPosting;
use App\Models\FinancialPosting;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Models\SupplierReturn;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class FinancialPostingReconciler
{
    public function __construct(private FinancialPostingCoordinator $postings) {}

    /** @return array<string, int> */
    public function repair(): array
    {
        $counts = [];
        $counts['sales'] = $this->each(Sale::query(), fn (Sale $model) => $this->postings->sale($model));
        $counts['payments'] = $this->each(Payment::query(), fn (Payment $model) => $this->postings->payment($model));
        $counts['purchase_receipts'] = $this->each(
            PurchaseReceipt::query()->where('total_kobo', '>', 0),
            fn (PurchaseReceipt $model) => $this->postings->purchaseReceipt($model),
        );
        $counts['supplier_bills'] = $this->each(
            SupplierBill::query()->whereNotNull('posted_at')->where('total_kobo', '>', 0),
            fn (SupplierBill $model) => $this->postings->supplierBill($model),
        );
        $counts['supplier_payments'] = $this->each(
            SupplierBillPayment::query(),
            fn (SupplierBillPayment $model) => $this->postings->supplierPayment($model),
        );
        $counts['sale_returns'] = $this->each(
            SaleReturn::query()->where('total_refund_kobo', '>', 0),
            fn (SaleReturn $model) => $this->postings->saleReturn($model),
        );
        $counts['purchase_returns'] = $this->each(
            PurchaseReturn::query()->where('total_kobo', '>', 0),
            fn (PurchaseReturn $model) => $this->postings->purchaseReturn($model),
        );
        $counts['supplier_returns'] = $this->each(
            SupplierReturn::query()->where('total_kobo', '>', 0),
            fn (SupplierReturn $model) => $this->postings->supplierReturn($model),
        );
        $counts['standalone_receipts'] = $this->each(
            StandaloneReceipt::query(),
            fn (StandaloneReceipt $model) => $this->postings->standaloneReceipt($model),
        );
        $counts['fixed_assets'] = $this->each(
            FixedAsset::query(),
            fn (FixedAsset $model) => $this->postings->fixedAsset($model),
        );
        $counts['stock_movements'] = $this->each(
            StockMovement::query(),
            fn (StockMovement $model) => $this->postings->stockMovement($model),
        );

        $depreciationCount = 0;
        AssetDepreciationPosting::query()
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$depreciationCount): void {
                foreach ($rows as $row) {
                    /** @var FixedAsset $asset */
                    $asset = FixedAsset::query()->findOrFail($row->fixed_asset_id);
                    $this->postings->depreciation(
                        $asset,
                        CarbonImmutable::parse($row->period_end),
                    );
                    $depreciationCount++;
                }
            });
        $counts['depreciation'] = $depreciationCount;

        $manualCount = 0;
        JournalEntry::query()
            ->whereNull('source_type')
            ->where('status', 'posted')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$manualCount): void {
                foreach ($rows as $entry) {
                    $this->postings->registerExistingJournal($entry, 'manual-reconciled');
                    $manualCount++;
                }
            });
        $counts['manual_journals'] = $manualCount;

        return $counts;
    }

    /** @return array{gaps:int,invalid:int,posted:int,non_posting:int} */
    public function audit(): array
    {
        $gaps = 0;
        $gaps += $this->missingCount(Sale::query(), Sale::class);
        $gaps += $this->missingCount(Payment::query(), Payment::class);
        $gaps += $this->missingCount(PurchaseReceipt::query()->where('total_kobo', '>', 0), PurchaseReceipt::class);
        $gaps += $this->missingCount(SupplierBill::query()->whereNotNull('posted_at')->where('total_kobo', '>', 0), SupplierBill::class);
        $gaps += $this->missingCount(SupplierBillPayment::query(), SupplierBillPayment::class);
        $gaps += $this->missingCount(SaleReturn::query()->where('total_refund_kobo', '>', 0), SaleReturn::class);
        $gaps += $this->missingCount(PurchaseReturn::query()->where('total_kobo', '>', 0), PurchaseReturn::class);
        $gaps += $this->missingCount(SupplierReturn::query()->where('total_kobo', '>', 0), SupplierReturn::class);
        $gaps += $this->missingCount(StandaloneReceipt::query(), StandaloneReceipt::class);
        $gaps += $this->missingCount(FixedAsset::query(), FixedAsset::class);
        $gaps += $this->missingCount(StockMovement::query(), StockMovement::class);
        $gaps += JournalEntry::query()
            ->whereNull('source_type')
            ->where('status', 'posted')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('financial_postings')
                    ->where('financial_postings.source_type', JournalEntry::class)
                    ->whereColumn('financial_postings.source_id', 'journal_entries.id');
            })
            ->count();
        $gaps += AssetDepreciationPosting::query()
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('financial_postings')
                    ->whereColumn(
                        'financial_postings.journal_entry_id',
                        'asset_depreciation_postings.journal_entry_id',
                    );
            })
            ->count();

        $invalid = 0;
        FinancialPosting::query()->with('journalEntry.lines')->orderBy('id')->chunkById(
            100,
            function ($rows) use (&$invalid): void {
                foreach ($rows as $posting) {
                    $classification = $posting->classification instanceof FinancialPostingClassification
                        ? $posting->classification->value
                        : (string) $posting->classification;
                    if ($classification === 'non_posting') {
                        if ($posting->journal_entry_id !== null) {
                            $invalid++;
                        }

                        continue;
                    }
                    $journal = $posting->journalEntry;
                    if (! $journal instanceof JournalEntry) {
                        $invalid++;

                        continue;
                    }
                    $debits = (int) $journal->lines->sum('debit_kobo');
                    $credits = (int) $journal->lines->sum('credit_kobo');
                    if ($debits <= 0 || $debits !== $credits) {
                        $invalid++;
                    }
                }
            },
        );

        return [
            'gaps' => $gaps,
            'invalid' => $invalid,
            'posted' => FinancialPosting::query()->where('classification', 'posted')->count(),
            'non_posting' => FinancialPosting::query()->where('classification', 'non_posting')->count(),
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): mixed  $callback
     */
    private function each(Builder $query, callable $callback): int
    {
        $count = 0;
        $query->orderBy('id')->chunkById(100, function ($rows) use ($callback, &$count): void {
            foreach ($rows as $row) {
                $callback($row);
                $count++;
            }
        });

        return $count;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function missingCount(Builder $query, string $sourceType): int
    {
        $table = $query->getModel()->getTable();

        return $query->whereNotExists(
            function ($subquery) use ($sourceType, $table): void {
                $subquery->selectRaw('1')
                    ->from('financial_postings')
                    ->where('financial_postings.source_type', $sourceType)
                    ->whereColumn(
                        'financial_postings.source_id',
                        $table.'.id',
                    );
            },
        )->count();
    }
}
