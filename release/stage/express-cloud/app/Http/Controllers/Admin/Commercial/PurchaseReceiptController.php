<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commercial;

use App\Actions\Commercial\RecordPurchaseReceipt;
use App\Http\Requests\Commercial\StorePurchaseReceiptRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class PurchaseReceiptController
{
    public function __construct(
        private RecordPurchaseReceipt $record,
    ) {}

    public function index(): View
    {
        return view('admin.commercial.purchase-receipts', [
            'receipts' => PurchaseReceipt::query()
                ->orderByDesc('purchased_at')
                ->paginate(40),
        ]);
    }

    public function create(): View
    {
        return view('admin.commercial.purchase-receipt-create', [
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'sku',
                    'default_cost_price_kobo',
                    'track_inventory',
                ]),
        ]);
    }

    public function store(
        StorePurchaseReceiptRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        /** @var Supplier $supplier */
        $supplier = Supplier::query()->findOrFail(
            $request->string('supplier_id')->toString(),
        );
        /** @var Branch $branch */
        $branch = Branch::query()->findOrFail(
            $request->string('branch_id')->toString(),
        );

        $receipt = $this->record->execute(
            $request,
            $supplier,
            $branch,
            $actor,
            $request->array('lines'),
            $request->string('purchased_at')->toString(),
            $request->filled('supplier_reference')
                ? $request->string('supplier_reference')->toString()
                : null,
            $request->filled('notes')
                ? $request->string('notes')->toString()
                : null,
        );

        return redirect()
            ->route('admin.commercial.purchases.index')
            ->with(
                'status',
                "Purchase {$receipt->receipt_number} recorded.",
            );
    }
}
