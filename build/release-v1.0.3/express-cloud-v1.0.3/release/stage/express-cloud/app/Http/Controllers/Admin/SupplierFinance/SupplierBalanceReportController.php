<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SupplierFinance;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SupplierBalanceReportController
{
    public function __invoke(): View
    {
        return view('admin.reports.supplier-balances', [
            'suppliers' => Supplier::query()
                ->select([
                    'suppliers.id',
                    'suppliers.supplier_code',
                    'suppliers.company_name',
                ])
                ->selectSub(
                    static function (Builder $query): void {
                        $query->from('supplier_bills')
                            ->selectRaw(
                                'COALESCE(SUM(total_kobo - paid_kobo), 0)',
                            )
                            ->whereColumn(
                                'supplier_bills.supplier_id',
                                'suppliers.id',
                            )
                            ->whereIn('status', ['open', 'partial']);
                    },
                    'outstanding_kobo',
                )
                ->orderByDesc('outstanding_kobo')
                ->orderBy('company_name')
                ->cursorPaginate((int) config(
                    'supplier-finance.pagination.supplier_balances',
                    50,
                )),
            'totalOutstandingKobo' => (int) DB::table(
                'supplier_bills',
            )
                ->whereIn('status', ['open', 'partial'])
                ->selectRaw(
                    'COALESCE(SUM(total_kobo - paid_kobo), 0) AS total',
                )
                ->value('total'),
        ]);
    }
}
