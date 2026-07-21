<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Sale;
use App\Services\Organisation\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * "Invoice reissue" — edit a previously recorded sale by voiding it
 * (correctly reversing its stock and accounting impact via VoidSale) and
 * recording a brand new sale from the edited line items and payments
 * (correctly computing stock/accounting impact via CreateSale), linking
 * the two together so the history is traceable and the new document can
 * be reprinted immediately.
 */
final readonly class ReissueSale
{
    public function __construct(
        private VoidSale $void,
        private CreateSale $create,
        private AuditLogger $audit,
    ) {}

    public function execute(
        StoreSaleRequest $request,
        Sale $original,
        Account $actor,
        string $reason,
    ): Sale {
        return DB::transaction(function () use (
            $request,
            $original,
            $actor,
            $reason,
        ): Sale {
            $this->void->execute(
                $request,
                $original,
                $actor,
                "Reissued: {$reason}",
            );

            $replacement = $this->create->execute($request, $actor);

            $replacement->forceFill([
                'reissued_from_sale_id' => $original->getKey(),
            ])->save();

            $this->audit->record(
                $request,
                'sale.reissued',
                'sale',
                $replacement,
                before: ['original_sale_code' => $original->sale_code],
                after: ['replacement_sale_code' => $replacement->sale_code, 'reason' => $reason],
            );

            return $replacement;
        });
    }
}
