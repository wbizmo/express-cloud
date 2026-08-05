<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Procurement;

use App\Models\Account;
use App\Models\GoodsReceipt;
use App\Models\LandedCostAllocation;
use App\Models\OperationRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Catalog\MoneyInput;
use App\Services\Operations\CommandBoundary;
use App\Services\Organisation\BranchAccess;
use App\Services\Procurement\EnterpriseProcurementWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class EnterpriseProcurementController
{
    public function __construct(
        private EnterpriseProcurementWorkflow $workflow,
        private MoneyInput $money,
        private CommandBoundary $commands,
        private BranchAccess $branches,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $warehouseIds = $this->branches->scope($actor, Warehouse::query())->select('id');

        return view('admin.procurement.enterprise', [
            'requisitions' => PurchaseRequisition::query()
                ->whereIn('warehouse_id', clone $warehouseIds)
                ->with(['warehouse:id,name', 'lines'])->latest()->paginate(10),
            'orders' => PurchaseOrder::query()
                ->when(
                    ! $actor->is_allowed_all_branches,
                    fn ($query) => $query->whereIn('branch_id', $this->branches->allowedBranchIds($actor)),
                )
                ->with(['supplier:id,company_name', 'warehouse:id,name', 'lines'])
                ->latest()->limit(30)->get(),
            'receipts' => GoodsReceipt::query()
                ->whereIn('warehouse_id', clone $warehouseIds)
                ->with(['purchaseOrder:id,order_number', 'warehouse:id,name'])
                ->latest('received_at')->limit(30)->get(),
            'warehouses' => $this->branches->scope($actor, Warehouse::query())
                ->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'active')
                ->orderBy('name')->get(['id', 'name', 'sku']),
            'suppliers' => Supplier::query()->orderBy('company_name')
                ->get(['id', 'company_name']),
        ]);
    }

    public function requisition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'ulid'],
            'reason' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'needed_on' => ['nullable', 'date'],
            'product_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $warehouse = $this->warehouseForActor($actor, $validated['warehouse_id']);

        $this->commands->execute(
            'procurement.requisition.create',
            $validated['idempotency_key'],
            $validated,
            $actor,
            (string) $warehouse->branch_id,
            fn (OperationRequest $operation): PurchaseRequisition => $this->workflow->requisition(
                $actor,
                $warehouse,
                $validated['reason'],
                [[
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'estimated_unit_cost_kobo' => $this->money->toKobo(
                        $validated['estimated_unit_cost'] ?? 0,
                    ) ?? 0,
                ]],
                $validated['priority'],
                $validated['needed_on'] ?? null,
                $operation,
            ),
        );

        return back()->with('status', 'Purchase requisition submitted.');
    }

    public function approve(
        Request $request,
        PurchaseRequisition $requisition,
    ): RedirectResponse {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $this->branches->enforce($actor, (string) $requisition->branch_id);

        $this->commands->execute(
            'procurement.requisition.approve',
            $validated['idempotency_key'],
            ['requisition_id' => (string) $requisition->getKey()],
            $actor,
            (string) $requisition->branch_id,
            fn (OperationRequest $operation): PurchaseRequisition => $this->workflow
                ->approve($requisition, $actor),
        );

        return back()->with('status', 'Purchase requisition approved.');
    }

    public function convert(
        Request $request,
        PurchaseRequisition $requisition,
    ): RedirectResponse {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'supplier_id' => ['required', 'ulid'],
            'expected_at' => ['nullable', 'date'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $this->branches->enforce($actor, (string) $requisition->branch_id);
        /** @var Supplier $supplier */
        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);

        $this->commands->execute(
            'procurement.requisition.convert',
            $validated['idempotency_key'],
            [
                'requisition_id' => (string) $requisition->getKey(),
                ...$validated,
            ],
            $actor,
            (string) $requisition->branch_id,
            fn (OperationRequest $operation): PurchaseOrder => $this->workflow->convertToOrder(
                $requisition,
                $supplier,
                $actor,
                $validated['expected_at'] ?? null,
            ),
        );

        return back()->with('status', 'Purchase order created from approved requisition.');
    }

    public function receive(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'ulid'],
            'line_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'accepted_quantity' => ['nullable', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'quarantine_quantity' => ['nullable', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'expires_on' => ['nullable', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $this->branches->enforce($actor, (string) $order->branch_id);
        $warehouse = $this->warehouseForActor($actor, $validated['warehouse_id']);
        if ((string) $warehouse->branch_id !== (string) $order->branch_id) {
            throw new \DomainException(
                'Goods must be received into a warehouse owned by the purchase-order branch.',
            );
        }

        $this->commands->execute(
            'procurement.goods-receipt.create',
            $validated['idempotency_key'],
            ['purchase_order_id' => (string) $order->getKey(), ...$validated],
            $actor,
            (string) $order->branch_id,
            fn (OperationRequest $operation): GoodsReceipt => $this->workflow->receive(
                $order,
                $warehouse,
                $actor,
                [[
                    'line_id' => $validated['line_id'],
                    'quantity' => $validated['quantity'],
                    'accepted_quantity' => $validated['accepted_quantity'] ?? $validated['quantity'],
                    'quarantine_quantity' => $validated['quarantine_quantity'] ?? 0,
                    'batch_number' => $validated['batch_number'] ?? null,
                    'expires_on' => $validated['expires_on'] ?? null,
                ]],
                $validated['supplier_reference'] ?? null,
                $validated['notes'] ?? null,
                $operation,
            ),
        );

        return back()->with('status', 'Goods receipt posted; backorders and warehouse valuation were updated.');
    }

    public function landedCost(Request $request, GoodsReceipt $receipt): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'allocation_method' => ['required', 'in:value,quantity,equal'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        /** @var Warehouse $warehouse */
        $warehouse = $receipt->warehouse()->firstOrFail();
        $this->branches->enforce($actor, (string) $warehouse->branch_id);

        $this->commands->execute(
            'procurement.landed-cost.allocate',
            $validated['idempotency_key'],
            ['goods_receipt_id' => (string) $receipt->getKey(), ...$validated],
            $actor,
            (string) $warehouse->branch_id,
            fn (OperationRequest $operation): LandedCostAllocation => $this->workflow
                ->allocateLandedCost(
                    $receipt,
                    $actor,
                    $validated['description'],
                    $this->money->toKobo($validated['amount']) ?? 0,
                    $validated['allocation_method'],
                    $operation,
                ),
        );

        return back()->with('status', 'Landed cost allocated to received inventory and posted to the ledger.');
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
