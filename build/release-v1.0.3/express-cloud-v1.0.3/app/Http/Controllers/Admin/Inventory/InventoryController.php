<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Inventory;

use App\Enums\Inventory\AdjustmentReason;
use App\Http\Requests\Admin\Inventory\StockAdjustmentRequest;
use App\Http\Requests\Admin\Inventory\StockIntakeRequest;
use App\Http\Requests\Admin\Inventory\StockTransferRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class InventoryController
{
    public function __construct(
        private StockLedger $ledger,
        private Quantity $quantity,
        private MoneyInput $money,
        private AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $branchId = $request->string('branch')->toString();

        return view('admin.inventory.index', [
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'stocks' => ProductBranchStock::query()
                ->with(['product:id,name,sku,track_inventory', 'branch:id,name'])
                ->when(
                    $branchId !== '',
                    static fn ($query) => $query->where('branch_id', $branchId),
                )
                ->orderBy('branch_id')
                ->cursorPaginate(50),
            'products' => Product::query()
                ->where('track_inventory', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'reasons' => AdjustmentReason::cases(),
        ]);
    }

    public function movements(): View
    {
        return view('admin.inventory.movements', [
            'movements' => StockMovement::query()
                ->with([
                    'product:id,name,sku',
                    'branch:id,name',
                    'account:id,first_name,last_name',
                ])
                ->orderByDesc('occurred_at')
                ->cursorPaginate(75),
            'quantity' => $this->quantity,
        ]);
    }

    public function intake(
        StockIntakeRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $branch = Branch::query()->findOrFail(
            $request->string('branch_id')->toString(),
        );

        $movement = $this->ledger->intake(
            $product,
            $branch,
            $actor,
            $this->quantity->toMilliunits(
                $request->string('quantity')->toString(),
            ),
            $this->money->toKobo($request->input('unit_cost')),
            'manual_stock_intake',
            null,
            $request->string('reference_note')->trim()->toString(),
        );

        $this->audit->record(
            $request,
            'stock.intake',
            'stock_movement',
            $movement,
            $branch,
            after: [
                'product_id' => (string) $product->getKey(),
                'quantity_delta_milliunits' => $movement->quantity_delta_milliunits,
                'balance_after_milliunits' => $movement->balance_after_milliunits,
            ],
        );

        return back()->with('status', 'Stock intake recorded.');
    }

    public function transfer(
        StockTransferRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $source = Branch::query()->findOrFail(
            $request->string('source_branch_id')->toString(),
        );
        $destination = Branch::query()->findOrFail(
            $request->string('destination_branch_id')->toString(),
        );

        $movements = $this->ledger->transfer(
            $product,
            $source,
            $destination,
            $actor,
            $this->quantity->toMilliunits(
                $request->string('quantity')->toString(),
            ),
            $request->string('reference_note')->trim()->toString(),
        );

        $this->audit->record(
            $request,
            'stock.transfer',
            'stock_transfer',
            (string) $movements['out']->correlation_id,
            $source,
            after: [
                'product_id' => (string) $product->getKey(),
                'source_branch_id' => (string) $source->getKey(),
                'destination_branch_id' => (string) $destination->getKey(),
                'quantity_milliunits' => abs(
                    $movements['out']->quantity_delta_milliunits,
                ),
            ],
        );

        return back()->with('status', 'Stock transfer completed.');
    }

    public function adjust(
        StockAdjustmentRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $branch = Branch::query()->findOrFail(
            $request->string('branch_id')->toString(),
        );

        $movement = $this->ledger->adjust(
            $product,
            $branch,
            $actor,
            $this->quantity->toMilliunits(
                $request->string('quantity_delta')->toString(),
            ),
            $request->string('reason_code')->toString(),
            $request->string('reference_note')->trim()->toString(),
        );

        $this->audit->record(
            $request,
            'stock.adjustment',
            'stock_movement',
            $movement,
            $branch,
            after: [
                'product_id' => (string) $product->getKey(),
                'reason_code' => $movement->reason_code,
                'quantity_delta_milliunits' => $movement->quantity_delta_milliunits,
                'balance_after_milliunits' => $movement->balance_after_milliunits,
            ],
        );

        return back()->with('status', 'Stock adjustment recorded.');
    }
}
