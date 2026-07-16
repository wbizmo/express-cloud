<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Services\Reports\Exports\CsvExport;
use App\Services\Reports\StaffPerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReportExportController
{
    public function __construct(
        private CsvExport $csv,
        private StaffPerformanceReport $staff,
    ) {}

    public function sales(Request $request): StreamedResponse
    {
        [$from, $to, $branch] = $this->filters($request);

        $rows = DB::table('sales')
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->leftJoin(
                'customers',
                'customers.id',
                '=',
                'sales.customer_id',
            )
            ->leftJoin(
                'accounts',
                'accounts.id',
                '=',
                'sales.sold_by_account_id',
            )
            ->whereBetween('sales.sale_date', [$from, $to])
            ->when(
                $branch !== null,
                static fn ($query) => $query->where(
                    'sales.branch_id',
                    $branch,
                ),
            )
            ->orderBy('sales.sale_date')
            ->orderBy('sales.sale_code')
            ->get([
                'sales.sale_code',
                'sales.sale_type',
                'sales.sale_date',
                'sales.status',
                'sales.grand_total_kobo',
                'sales.paid_amount_kobo',
                'branches.name AS branch_name',
                'customers.name AS customer_name',
                'accounts.first_name',
                'accounts.last_name',
            ])
            ->map(
                static fn (object $row): array => [
                    (string) $row->sale_code,
                    (string) $row->sale_type,
                    (string) $row->sale_date,
                    (string) $row->status,
                    number_format(
                        ((int) $row->grand_total_kobo) / 100,
                        2,
                        '.',
                        '',
                    ),
                    number_format(
                        ((int) $row->paid_amount_kobo) / 100,
                        2,
                        '.',
                        '',
                    ),
                    (string) $row->branch_name,
                    (string) ($row->customer_name ?? 'Walk-in'),
                    trim(
                        (string) $row->first_name
                        .' '
                        .(string) $row->last_name,
                    ),
                ],
            )
            ->all();

        return $this->csv->download(
            "sales-{$from}-{$to}.csv",
            [
                'Sale Code',
                'Type',
                'Date',
                'Status',
                'Total NGN',
                'Paid NGN',
                'Branch',
                'Customer',
                'Staff',
            ],
            $rows,
        );
    }

    public function staff(Request $request): StreamedResponse
    {
        [$from, $to, $branch] = $this->filters($request);

        $rows = $this->staff->run($from, $to, $branch)
            ->map(
                static fn (object $row): array => [
                    trim(
                        (string) $row->first_name
                        .' '
                        .(string) $row->last_name,
                    ),
                    (int) $row->sales_count,
                    number_format(
                        ((int) $row->revenue_kobo) / 100,
                        2,
                        '.',
                        '',
                    ),
                    (int) $row->units_milliunits,
                    (int) $row->customers_served,
                ],
            )
            ->all();

        return $this->csv->download(
            "staff-performance-{$from}-{$to}.csv",
            [
                'Staff',
                'Sales Count',
                'Revenue NGN',
                'Units Milliunits',
                'Customers Served',
            ],
            $rows,
        );
    }

    public function lowStock(): StreamedResponse
    {
        $rows = DB::table('low_stock_alerts')
            ->join(
                'products',
                'products.id',
                '=',
                'low_stock_alerts.product_id',
            )
            ->join(
                'branches',
                'branches.id',
                '=',
                'low_stock_alerts.branch_id',
            )
            ->whereNull('low_stock_alerts.resolved_at')
            ->orderBy('branches.name')
            ->orderBy('products.name')
            ->get([
                'products.name AS product_name',
                'products.sku',
                'branches.name AS branch_name',
                'low_stock_alerts.quantity_milliunits',
                'low_stock_alerts.minimum_stock_milliunits',
            ])
            ->map(
                static fn (object $row): array => [
                    (string) $row->product_name,
                    (string) $row->sku,
                    (string) $row->branch_name,
                    (int) $row->quantity_milliunits,
                    (int) $row->minimum_stock_milliunits,
                ],
            )
            ->all();

        return $this->csv->download(
            'low-stock.csv',
            [
                'Product',
                'SKU',
                'Branch',
                'Quantity Milliunits',
                'Minimum Milliunits',
            ],
            $rows,
        );
    }

    /** @return array{string, string, string|null} */
    private function filters(Request $request): array
    {
        return [
            $request->date('from')?->toDateString()
                ?? now()->startOfMonth()->toDateString(),
            $request->date('to')?->toDateString()
                ?? today()->toDateString(),
            $request->filled('branch')
                ? $request->string('branch')->toString()
                : null,
        ];
    }
}
