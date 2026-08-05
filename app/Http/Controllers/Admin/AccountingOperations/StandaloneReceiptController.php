<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AccountingOperations;

use App\Http\Requests\Admin\AccountingOperations\StoreStandaloneReceiptRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StandaloneReceipt;
use App\Services\Inventory\StockLedger;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class StandaloneReceiptController
{
    public function __construct(
        private AuditLogger $audit,
        private StockLedger $ledger,
    ) {}

    public function index(): View
    {
        return view('admin.accounting-operations.receipts.index', [
            'receipts' => StandaloneReceipt::query()
                ->with(['branch', 'createdBy'])
                ->orderByDesc('created_at')
                ->paginate(config('pagination.default', 10)),
        ]);
    }

    public function create(): View
    {
        return view('admin.accounting-operations.receipts.create', [
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'products' => Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(StoreStandaloneReceiptRequest $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();

        $receipt = StandaloneReceipt::create([
            'branch_id' => $request->branch_id,
            'purchased_at' => $request->purchased_at,
            'supplier_reference' => $request->supplier_reference,
            'notes' => $request->notes,
            'created_by_account_id' => $actor->id,
        ]);

        $totalKobo = 0;
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = $item['unit_price_kobo'] ?? $product->default_cost_price_kobo ?? 0;
            $total = $item['quantity'] * $unitPrice;
            $receipt->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price_kobo' => $unitPrice,
                'total_kobo' => $total,
            ]);
            $totalKobo += $total;

            // ✅ Update stock
            $this->ledger->intake(
                $product,
                $receipt->branch,
                $actor,
                $item['quantity'] * 1000, // assuming quantity in units, convert to milliunits
                0,
                'direct_purchase',
                $receipt->id,
                'Direct purchase receipt '.$receipt->id
            );
        }

        $receipt->update(['total_kobo' => $totalKobo]);

        $this->audit->record(
            $request,
            'standalone_receipt.created',
            'standalone_receipt',
            $receipt,
            after: ['total_kobo' => $totalKobo]
        );

        return redirect()->route('admin.accounting-operations.receipts.index')
            ->with('status', 'Receipt created and stock updated.');
    }
}
