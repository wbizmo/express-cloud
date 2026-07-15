<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SupplierFinance;

use App\Actions\SupplierFinance\CreateSupplierReturn;
use App\Enums\SupplierFinance\SupplierReturnStatus;
use App\Http\Requests\Admin\SupplierFinance\StoreSupplierReturnRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class SupplierReturnController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.supplier-finance.returns-index', [
            'returns' => SupplierReturn::query()
                ->with([
                    'supplier:id,company_name,supplier_code',
                    'branch:id,name',
                ])
                ->orderByDesc('return_date')
                ->cursorPaginate((int) config(
                    'supplier-finance.pagination.returns',
                    40,
                )),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'supplier_code']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'bills' => SupplierBill::query()
                ->whereIn('status', ['open', 'partial', 'paid'])
                ->orderByDesc('bill_date')
                ->limit(100)
                ->get([
                    'id',
                    'bill_number',
                    'supplier_id',
                    'branch_id',
                ]),
            'products' => Product::query()
                ->where('track_inventory', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'sku',
                    'default_cost_price_kobo',
                ]),
        ]);
    }

    public function store(
        StoreSupplierReturnRequest $request,
        CreateSupplierReturn $creator,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $return = $creator->execute(
            $actor,
            $request->string('supplier_id')->toString(),
            $request->string('branch_id')->toString(),
            $request->filled('supplier_bill_id')
                ? $request->string('supplier_bill_id')->toString()
                : null,
            $request->string('reason')->trim()->toString(),
            $request->string('reference_note')->trim()->toString(),
            $request->array('lines'),
        );

        $this->audit->record(
            $request,
            'supplier-return.confirmed',
            'supplier_return',
            $return,
            after: [
                'return_number' => $return->return_number,
                'supplier_id' => (string) $return->supplier_id,
                'total_kobo' => $return->total_kobo,
                'status' => $return->status
                    instanceof SupplierReturnStatus
                    ? $return->status->value
                    : (string) $return->status,
            ],
        );

        return back()->with('status', 'Supplier return confirmed.');
    }
}
