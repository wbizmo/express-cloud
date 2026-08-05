<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Inventory;

use App\Models\Account;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Models\WarehouseStockBalance;
use App\Services\Inventory\Quantity;
use App\Services\Inventory\WarehouseStockLedger;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\BranchAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class WarehouseController
{
    public function __construct(
        private WarehouseStockLedger $stock,
        private Quantity $quantity,
        private CommandBoundary $commands,
        private BranchAccess $branches,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $warehouseId = $request->string('warehouse_id')->toString();
        $warehouseQuery = $this->branches->scope($actor, Warehouse::query());

        if ($warehouseId !== '') {
            /** @var Warehouse $selected */
            $selected = (clone $warehouseQuery)->whereKey($warehouseId)->firstOrFail();
            $warehouseId = (string) $selected->getKey();
        }

        return view('admin.inventory.warehouses', [
            'warehouseId' => $warehouseId,
            'warehouses' => $this->branches->scope($actor, Warehouse::query())
                ->with('branch:id,name')->orderBy('name')->get(),
            'branches' => $this->branches->scope($actor, Branch::query(), 'id')
                ->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->where('track_inventory', true)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name', 'sku']),
            'balances' => WarehouseStockBalance::query()
                ->with(['warehouse.branch:id,name', 'product:id,name,sku'])
                ->whereIn(
                    'warehouse_id',
                    $this->branches->scope($actor, Warehouse::query())->select('id'),
                )
                ->when(
                    $warehouseId !== '',
                    static fn (Builder $query): Builder => $query->where('warehouse_id', $warehouseId),
                )
                ->orderByDesc('last_movement_at')
                ->cursorPaginate(config('pagination.default', 10))
                ->withQueryString(),
            'reservations' => StockReservation::query()
                ->with('warehouse:id,name')
                ->whereIn(
                    'warehouse_id',
                    $this->branches->scope($actor, Warehouse::query())->select('id'),
                )
                ->where('status', 'active')->latest()->limit(20)->get(),
            'counts' => StockCount::query()->with('warehouse:id,name')
                ->whereIn(
                    'warehouse_id',
                    $this->branches->scope($actor, Warehouse::query())->select('id'),
                )
                ->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'branch_id' => ['required', 'ulid'],
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:store,warehouse,transit,quarantine'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
            'allows_sales' => ['nullable', 'boolean'],
            'allows_receipts' => ['nullable', 'boolean'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $this->branches->enforce($actor, $validated['branch_id']);

        $this->commands->execute(
            'warehouse.create',
            $validated['idempotency_key'],
            $validated,
            $actor,
            $validated['branch_id'],
            static function (OperationRequest $operation) use ($validated): Warehouse {
                if ((bool) ($validated['is_default'] ?? false)) {
                    Warehouse::query()->where('branch_id', $validated['branch_id'])
                        ->update(['is_default' => false]);
                }

                return Warehouse::query()->create([
                    'branch_id' => $validated['branch_id'],
                    'code' => $validated['code'],
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'address' => $validated['address'] ?? null,
                    'status' => 'active',
                    'is_default' => (bool) ($validated['is_default'] ?? false),
                    'allows_sales' => (bool) ($validated['allows_sales'] ?? false),
                    'allows_receipts' => (bool) ($validated['allows_receipts'] ?? true),
                ]);
            },
        );

        return back()->with('status', 'Warehouse created.');
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'product_id' => ['required', 'ulid'],
            'source_warehouse_id' => ['required', 'ulid', 'different:destination_warehouse_id'],
            'destination_warehouse_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'condition' => ['required', 'in:available,quarantine,damaged'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        /** @var Product $product */
        $product = Product::query()->findOrFail($validated['product_id']);
        $source = $this->warehouseForActor($actor, $validated['source_warehouse_id']);
        $destination = $this->warehouseForActor($actor, $validated['destination_warehouse_id']);

        $this->commands->execute(
            'warehouse.transfer',
            $validated['idempotency_key'],
            $validated,
            $actor,
            (string) $source->branch_id,
            function (OperationRequest $operation) use (
                $product,
                $source,
                $destination,
                $actor,
                $validated,
            ): StockMovement {
                $movements = $this->stock->transfer(
                    $product,
                    $source,
                    $destination,
                    $actor,
                    $this->quantity->toMilliunits($validated['quantity']),
                    condition: $validated['condition'],
                    operation: $operation,
                    note: $validated['note'] ?? null,
                );

                return $movements['out'];
            },
        );

        return back()->with('status', 'Warehouse transfer completed with paired append-only movements.');
    }

    public function reserve(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'ulid'],
            'product_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'reference_type' => ['required', 'string', 'max:100'],
            'reference_id' => ['required', 'string', 'max:64'],
            'expires_at' => ['nullable', 'date'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $warehouse = $this->warehouseForActor($actor, $validated['warehouse_id']);
        /** @var Product $product */
        $product = Product::query()->findOrFail($validated['product_id']);

        $this->commands->execute(
            'warehouse.reserve',
            $validated['idempotency_key'],
            $validated,
            $actor,
            (string) $warehouse->branch_id,
            fn (OperationRequest $operation): StockReservation => $this->stock->reserve(
                $product,
                $warehouse,
                $actor,
                $this->quantity->toMilliunits($validated['quantity']),
                $validated['reference_type'],
                $validated['reference_id'],
                expiresAt: isset($validated['expires_at'])
                    ? CarbonImmutable::parse($validated['expires_at'])
                    : null,
                operation: $operation,
            ),
        );

        return back()->with('status', 'Warehouse stock reserved.');
    }

    public function release(
        Request $request,
        StockReservation $reservation,
    ): RedirectResponse {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $warehouse = $this->warehouseForActor($actor, (string) $reservation->warehouse_id);

        $this->commands->execute(
            'warehouse.reservation.release',
            $validated['idempotency_key'],
            ['reservation_id' => (string) $reservation->getKey()],
            $actor,
            (string) $warehouse->branch_id,
            fn (OperationRequest $operation): StockReservation => $this->stock
                ->releaseReservation($reservation, $actor, $operation),
        );

        return back()->with('status', 'Stock reservation released.');
    }

    public function condition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'ulid'],
            'product_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'from_condition' => ['required', 'in:available,quarantine,damaged'],
            'to_condition' => ['required', 'different:from_condition', 'in:available,quarantine,damaged'],
            'reason_code' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $warehouse = $this->warehouseForActor($actor, $validated['warehouse_id']);
        /** @var Product $product */
        $product = Product::query()->findOrFail($validated['product_id']);

        $this->commands->execute(
            'warehouse.condition-change',
            $validated['idempotency_key'],
            $validated,
            $actor,
            (string) $warehouse->branch_id,
            function (OperationRequest $operation) use (
                $product,
                $warehouse,
                $actor,
                $validated,
            ): StockMovement {
                $movements = $this->stock->changeCondition(
                    $product,
                    $warehouse,
                    $actor,
                    $this->quantity->toMilliunits($validated['quantity']),
                    $validated['from_condition'],
                    $validated['to_condition'],
                    $validated['reason_code'],
                    note: $validated['note'] ?? null,
                    operation: $operation,
                );

                return $movements['out'];
            },
        );

        return back()->with('status', 'Stock condition updated with paired append-only movements.');
    }

    public function count(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'ulid'],
            'product_id' => ['required', 'ulid'],
            'counted_quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'condition' => ['required', 'in:available,quarantine,damaged'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $warehouse = $this->warehouseForActor($actor, $validated['warehouse_id']);
        /** @var Product $product */
        $product = Product::query()->findOrFail($validated['product_id']);
        $counted = $this->quantity->toMilliunits($validated['counted_quantity']);

        $this->commands->execute(
            'warehouse.stock-count',
            $validated['idempotency_key'],
            $validated,
            $actor,
            (string) $warehouse->branch_id,
            function (OperationRequest $operation) use (
                $warehouse,
                $product,
                $actor,
                $validated,
                $counted,
            ): StockCount {
                $balance = WarehouseStockBalance::query()
                    ->where('warehouse_id', $warehouse->getKey())
                    ->where('product_id', $product->getKey())
                    ->where('condition', $validated['condition'])
                    ->lockForUpdate()->first();
                $system = $balance instanceof WarehouseStockBalance
                    ? $balance->quantity_milliunits
                    : 0;
                $count = StockCount::query()->create([
                    'count_number' => 'CNT-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                    'operation_request_id' => $operation->getKey(),
                    'warehouse_id' => $warehouse->getKey(),
                    'opened_by_account_id' => $actor->getKey(),
                    'approved_by_account_id' => $actor->getKey(),
                    'status' => 'posted',
                    'counted_at' => now(),
                    'approved_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);
                StockCountLine::query()->create([
                    'stock_count_id' => $count->getKey(),
                    'product_id' => $product->getKey(),
                    'condition' => $validated['condition'],
                    'identity_hash' => hash('sha256', implode('|', [
                        (string) $count->getKey(),
                        (string) $product->getKey(),
                        $validated['condition'],
                    ])),
                    'system_quantity_milliunits' => $system,
                    'counted_quantity_milliunits' => $counted,
                    'variance_milliunits' => $counted - $system,
                    'reason_code' => 'physical-count',
                ]);
                $this->stock->countAdjustment(
                    $product,
                    $warehouse,
                    $actor,
                    $counted,
                    $validated['condition'],
                    referenceId: (string) $count->getKey(),
                    note: $validated['notes'] ?? null,
                    operation: $operation,
                );

                return $count;
            },
        );

        return back()->with('status', 'Stock count posted and variance recorded.');
    }

    private function warehouseForActor(Account $actor, string $warehouseId): Warehouse
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->branches->scope($actor, Warehouse::query())
            ->whereKey($warehouseId)
            ->firstOrFail();

        return $warehouse;
    }
}
