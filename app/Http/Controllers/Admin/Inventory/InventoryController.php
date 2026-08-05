<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Inventory;

use App\Enums\Inventory\AdjustmentReason;
use App\Http\Requests\Admin\Inventory\StockAdjustmentRequest;
use App\Http\Requests\Admin\Inventory\StockIntakeRequest;
use App\Http\Requests\Admin\Inventory\StockTransferRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use App\Services\Catalog\MoneyInput;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\StockLedger;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class InventoryController
{
    public function __construct(
        private StockLedger $ledger,
        private Quantity $quantity,
        private MoneyInput $money,
        private AuditLogger $audit,
        private CommandBoundary $commands,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $allowedBranchIds = $actor->is_allowed_all_branches
            ? null
            : $actor->branches()->pluck('branches.id');
        $branchId = $request->string('branch')->toString();

        if (
            $branchId !== ''
            && $allowedBranchIds !== null
            && ! $allowedBranchIds->contains($branchId)
        ) {
            abort(403, 'You do not have access to this branch.');
        }

        $branches = Branch::query()
            ->where('status', 'active')
            ->when(
                $allowedBranchIds !== null,
                static fn (Builder $query) => $query->whereIn(
                    'id',
                    $allowedBranchIds,
                ),
            )
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $products = Product::query()
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->with([
                'branchStock' => static fn ($query) => $query
                    ->when(
                        $allowedBranchIds !== null,
                        static fn ($stockQuery) => $stockQuery->whereIn(
                            'branch_id',
                            $allowedBranchIds,
                        ),
                    )
                    ->select([
                        'id',
                        'product_id',
                        'branch_id',
                        'quantity_milliunits',
                        'minimum_stock_milliunits',
                    ]),
                'branchPrices' => static fn ($query) => $query
                    ->when(
                        $allowedBranchIds !== null,
                        static fn ($priceQuery) => $priceQuery->whereIn(
                            'branch_id',
                            $allowedBranchIds,
                        ),
                    )
                    ->select([
                        'id',
                        'product_id',
                        'branch_id',
                        'price_kobo',
                    ]),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode', 'default_price_kobo'])
            ->map(static fn (Product $product): array => [
                'id' => (string) $product->getKey(),
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'default_price' => $product->default_price_kobo / 100,
                'stocks' => $product->branchStock->mapWithKeys(
                    static fn ($stock): array => [
                        (string) $stock->branch_id => [
                            'quantity' => $stock->quantity_milliunits / 1000,
                            'minimum' => $stock->minimum_stock_milliunits / 1000,
                        ],
                    ],
                ),
                'prices' => $product->branchPrices->mapWithKeys(
                    static fn ($price): array => [
                        (string) $price->branch_id => $price->price_kobo / 100,
                    ],
                ),
            ]);

        return view('admin.inventory.index', [
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'stocks' => ProductBranchStock::query()
                ->with([
                    'product:id,name,sku,track_inventory',
                    'branch:id,name',
                ])
                ->when(
                    $allowedBranchIds !== null,
                    static fn ($query) => $query->whereIn(
                        'branch_id',
                        $allowedBranchIds,
                    ),
                )
                ->when(
                    $branchId !== '',
                    static fn ($query) => $query->where(
                        'branch_id',
                        $branchId,
                    ),
                )
                ->orderBy('branch_id')
                ->cursorPaginate(config('pagination.default', 10))
                ->withQueryString(),
            'products' => $products,
            'reasons' => AdjustmentReason::cases(),
        ]);
    }

    public function movements(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();

        return view('admin.inventory.movements', [
            'movements' => StockMovement::query()
                ->with([
                    'product:id,name,sku',
                    'branch:id,name',
                    'account:id,first_name,last_name',
                ])
                ->when(
                    ! $actor->is_allowed_all_branches,
                    static fn ($query) => $query->whereIn(
                        'branch_id',
                        $actor->branches()->select('branches.id'),
                    ),
                )
                ->orderByDesc('occurred_at')
                ->cursorPaginate(config('pagination.default', 10)),
            'quantity' => $this->quantity,
        ]);
    }

    public function intake(StockIntakeRequest $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $branch = $this->authorizedBranch($request, 'branch_id');
        $movement = $this->executeMovement(
            'stock.intake',
            $request,
            $actor,
            $branch,
            fn (OperationRequest $operation): StockMovement => $this->ledger->intake(
                $product,
                $branch,
                $actor,
                $this->quantity->toMilliunits(
                    $request->string('quantity')->toString(),
                ),
                $this->money->toKobo($request->input('unit_cost')),
                'manual_stock_intake',
                (string) $operation->getKey(),
                $request->string('reference_note')->trim()->toString(),
                $operation,
            ),
        );

        if ($movement->wasRecentlyCreated) {
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
        }

        return back()->with('status', 'Stock intake recorded.');
    }

    public function transfer(StockTransferRequest $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $source = $this->authorizedBranch($request, 'source_branch_id');
        $destination = $this->authorizedBranch(
            $request,
            'destination_branch_id',
        );
        $movement = $this->executeMovement(
            'stock.transfer',
            $request,
            $actor,
            $source,
            function (OperationRequest $operation) use (
                $product,
                $source,
                $destination,
                $actor,
                $request,
            ): StockMovement {
                $movements = $this->ledger->transfer(
                    $product,
                    $source,
                    $destination,
                    $actor,
                    $this->quantity->toMilliunits(
                        $request->string('quantity')->toString(),
                    ),
                    $request->string('reference_note')->trim()->toString(),
                    $operation,
                );

                return $movements['out'];
            },
        );

        if ($movement->wasRecentlyCreated) {
            $this->audit->record(
                $request,
                'stock.transfer',
                'stock_transfer',
                (string) $movement->correlation_id,
                $source,
                after: [
                    'product_id' => (string) $product->getKey(),
                    'source_branch_id' => (string) $source->getKey(),
                    'destination_branch_id' => (string) $destination->getKey(),
                    'quantity_milliunits' => abs(
                        $movement->quantity_delta_milliunits,
                    ),
                ],
            );
        }

        return back()->with('status', 'Stock transfer completed.');
    }

    public function adjust(StockAdjustmentRequest $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $product = Product::query()->findOrFail(
            $request->string('product_id')->toString(),
        );
        $branch = $this->authorizedBranch($request, 'branch_id');
        $movement = $this->executeMovement(
            'stock.adjust',
            $request,
            $actor,
            $branch,
            fn (OperationRequest $operation): StockMovement => $this->ledger->adjust(
                $product,
                $branch,
                $actor,
                $this->quantity->toMilliunits(
                    $request->string('quantity_delta')->toString(),
                ),
                $request->string('reason_code')->toString(),
                $request->string('reference_note')->trim()->toString(),
                $operation,
            ),
        );

        if ($movement->wasRecentlyCreated) {
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
        }

        return back()->with('status', 'Stock adjustment recorded.');
    }

    /** @param \Closure(OperationRequest): StockMovement $callback */
    private function executeMovement(
        string $scope,
        Request $request,
        Account $actor,
        Branch $branch,
        \Closure $callback,
    ): StockMovement {
        $result = $this->commands->execute(
            $scope,
            $request->string('idempotency_key')->trim()->toString(),
            $request->all(),
            $actor,
            (string) $branch->getKey(),
            $callback,
        );

        if (! $result instanceof StockMovement) {
            throw new \LogicException(
                'The stock command returned an invalid result.',
            );
        }

        return $result;
    }

    private function authorizedBranch(Request $request, string $field): Branch
    {
        /** @var Account $actor */
        $actor = $request->user();

        return Branch::query()
            ->whereKey($request->string($field)->toString())
            ->when(
                ! $actor->is_allowed_all_branches,
                static fn ($query) => $query->whereIn(
                    'id',
                    $actor->branches()->select('branches.id'),
                ),
            )
            ->firstOrFail();
    }
}
