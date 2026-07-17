<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\FixedAsset;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\StockMovement;
use App\Support\Documents\OperationReportData;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class OperationReportFactory
{
    public function make(
        string $type,
        string $id,
    ): OperationReportData {
        return match ($type) {
            'standalone_receipt' => $this->standaloneReceipt($id),
            'purchase_receipt' => $this->purchaseReceipt($id),
            'purchase_return' => $this->purchaseReturn($id),
            'sale_return' => $this->saleReturn($id),
            'stock_operation' => $this->stockOperation($id),
            'fixed_asset' => $this->fixedAsset($id),
            default => throw new \InvalidArgumentException(
                "Unsupported operation report type: {$type}",
            ),
        };
    }

    private function standaloneReceipt(
        string $id,
    ): OperationReportData {
        /** @var StandaloneReceipt $receipt */
        $receipt = StandaloneReceipt::query()->findOrFail($id);

        return new OperationReportData(
            title: 'Payment Receipt',
            reference: $receipt->receipt_number,
            date: self::dateTime($receipt->received_at),
            rows: [[
                'Payer' => $receipt->payer_name,
                'Purpose' => $receipt->purpose,
                'Amount' => number_format(
                    $receipt->amount_kobo / 100,
                    2,
                ),
                'Reference' => $receipt->reference,
            ]],
            summary: [
                'Amount received' => number_format(
                    $receipt->amount_kobo / 100,
                    2,
                ),
                'Status' => self::enumValue($receipt->status),
            ],
            notes: $receipt->notes,
        );
    }

    private function purchaseReceipt(
        string $id,
    ): OperationReportData {
        /** @var PurchaseReceipt $purchase */
        $purchase = PurchaseReceipt::query()
            ->with('lines')
            ->findOrFail($id);

        $rows = [];

        foreach ($purchase->lines as $line) {
            $rows[] = [
                'Product ID' => $line->product_id,
                'Quantity' => number_format(
                    $line->quantity_milliunits / 1000,
                    3,
                ),
                'Unit cost' => number_format(
                    $line->unit_cost_kobo / 100,
                    2,
                ),
                'Line total' => number_format(
                    $line->line_total_kobo / 100,
                    2,
                ),
            ];
        }

        return new OperationReportData(
            title: 'Purchase Receipt',
            reference: $purchase->receipt_number,
            date: self::date($purchase->purchased_at),
            rows: $rows,
            summary: [
                'Subtotal' => number_format(
                    $purchase->subtotal_kobo / 100,
                    2,
                ),
                'Discount' => number_format(
                    $purchase->discount_kobo / 100,
                    2,
                ),
                'Tax' => number_format(
                    $purchase->tax_kobo / 100,
                    2,
                ),
                'Total' => number_format(
                    $purchase->total_kobo / 100,
                    2,
                ),
            ],
            notes: $purchase->notes,
        );
    }

    private function purchaseReturn(
        string $id,
    ): OperationReportData {
        /** @var PurchaseReturn $return */
        $return = PurchaseReturn::query()
            ->with('lines')
            ->findOrFail($id);

        $rows = [];

        foreach ($return->lines as $line) {
            $rows[] = [
                'Product ID' => $line->product_id,
                'Quantity' => number_format(
                    $line->quantity_milliunits / 1000,
                    3,
                ),
                'Unit cost' => number_format(
                    $line->unit_cost_kobo / 100,
                    2,
                ),
                'Line total' => number_format(
                    $line->line_total_kobo / 100,
                    2,
                ),
            ];
        }

        return new OperationReportData(
            title: 'Purchase Return',
            reference: $return->return_number,
            date: self::dateTime($return->returned_at),
            rows: $rows,
            summary: [
                'Total returned' => number_format(
                    $return->total_kobo / 100,
                    2,
                ),
                'Status' => self::enumValue($return->status),
                'Supplier credit reference' => (
                    $return->supplier_credit_reference
                ),
            ],
            notes: $return->reason,
        );
    }

    private function saleReturn(string $id): OperationReportData
    {
        /** @var SaleReturn $return */
        $return = SaleReturn::query()
            ->with('items')
            ->findOrFail($id);

        $rows = [];

        foreach ($return->items as $line) {
            $rows[] = [
                'Product ID' => $line->product_id,
                'Quantity' => number_format(
                    $line->quantity_milliunits / 1000,
                    3,
                ),
                'Refund' => number_format(
                    $line->refund_amount_kobo / 100,
                    2,
                ),
                'Restocked' => $line->restock ? 'Yes' : 'No',
            ];
        }

        return new OperationReportData(
            title: 'Sale Return',
            reference: $return->return_code,
            date: self::dateTime($return->returned_at),
            rows: $rows,
            summary: [
                'Refund total' => number_format(
                    $return->total_refund_kobo / 100,
                    2,
                ),
                'Status' => self::enumValue($return->status),
                'Refund method' => $return->refund_method,
            ],
            notes: $return->reason,
        );
    }

    private function stockOperation(string $id): OperationReportData
    {
        $movements = StockMovement::query()
            ->where('reference_id', $id)
            ->orderBy('occurred_at')
            ->get();

        if ($movements->isEmpty()) {
            throw new ModelNotFoundException;
        }

        $first = $movements->firstOrFail();
        $rows = [];

        foreach ($movements as $movement) {
            $rows[] = [
                'Product ID' => $movement->product_id,
                'Branch ID' => $movement->branch_id,
                'Movement' => self::enumValue($movement->movement_type),
                'Quantity change' => number_format(
                    $movement->quantity_delta_milliunits / 1000,
                    3,
                ),
                'Balance after' => number_format(
                    $movement->balance_after_milliunits / 1000,
                    3,
                ),
                'Time' => self::dateTime($movement->occurred_at),
            ];
        }

        return new OperationReportData(
            title: 'Stock Operation Report',
            reference: $id,
            date: self::dateTime($first->occurred_at),
            rows: $rows,
            summary: [
                'Reference type' => $first->reference_type,
                'Movement count' => $movements->count(),
            ],
            notes: $first->note,
        );
    }

    private function fixedAsset(string $id): OperationReportData
    {
        /** @var FixedAsset $asset */
        $asset = FixedAsset::query()->findOrFail($id);

        return new OperationReportData(
            title: 'Fixed Asset Record',
            reference: $asset->asset_code,
            date: self::date($asset->acquired_at),
            rows: [[
                'Name' => $asset->name,
                'Category' => $asset->category,
                'Serial number' => $asset->serial_number,
                'Location' => $asset->location,
            ]],
            summary: [
                'Cost' => number_format(
                    $asset->cost_kobo / 100,
                    2,
                ),
                'Salvage value' => number_format(
                    $asset->salvage_value_kobo / 100,
                    2,
                ),
                'Useful life (months)' => (
                    $asset->useful_life_months
                ),
                'Monthly depreciation' => number_format(
                    $asset->monthlyDepreciationKobo() / 100,
                    2,
                ),
                'Status' => self::enumValue($asset->status),
            ],
            notes: $asset->notes,
        );
    }

    private static function dateTime(mixed $value): string
    {
        return CarbonImmutable::parse((string) $value)
            ->toDateTimeString();
    }

    private static function date(mixed $value): string
    {
        return CarbonImmutable::parse((string) $value)
            ->toDateString();
    }

    private static function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum
            ? (string) $value->value
            : (string) $value;
    }
}
