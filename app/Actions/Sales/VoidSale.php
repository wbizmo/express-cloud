<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Actions\Commercial\CreateSaleReturn;
use App\Enums\Sales\SaleStatus;
use App\Models\Account;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Voiding a sale is implemented as returning 100% of every remaining line
 * (with restock enabled) through the same CreateSaleReturn action already
 * used for partial returns — this reuses the reversal logic that already
 * correctly restocks tracked items and posts the reversing journal entry,
 * rather than a second, independently-written reversal path that could
 * drift out of sync with it over time.
 */
final readonly class VoidSale
{
    public function __construct(
        private CreateSaleReturn $returns,
        private AuditLogger $audit,
    ) {}

    public function execute(
        Request $request,
        Sale $sale,
        Account $actor,
        string $reason,
    ): Sale {
        return DB::transaction(function () use (
            $request,
            $sale,
            $actor,
            $reason,
        ): Sale {
            $lines = $sale->items()->get()->map(
                function (SaleItem $item) {
                    $alreadyReturned = (int) SaleReturnItem::query()
                        ->where('sale_item_id', $item->getKey())
                        ->sum('quantity_milliunits');

                    $remaining = $item->quantity_milliunits
                        - $alreadyReturned;

                    return $remaining > 0 ? [
                        'sale_item_id' => $item->getKey(),
                        'quantity' => $remaining / 1000,
                        'restock' => true,
                    ] : null;
                },
            )->filter()->values()->all();

            if ($lines !== []) {
                $this->returns->execute(
                    $request,
                    $sale,
                    $actor,
                    $lines,
                    "Sale voided: {$reason}",
                    null,
                );
            }

            $before = ['status' => $sale->status->value];

            $sale->forceFill([
                'status' => SaleStatus::Cancelled,
            ])->save();

            $this->audit->record(
                $request,
                'sale.voided',
                'sale',
                $sale,
                before: $before,
                after: ['status' => SaleStatus::Cancelled->value, 'reason' => $reason],
            );

            return $sale->refresh();
        });
    }
}
