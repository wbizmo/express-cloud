<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Procurement;

use App\Actions\Procurement\CreatePurchaseOrder;
use App\Actions\Procurement\ReceivePurchaseOrder;
use App\Enums\Procurement\PurchaseOrderStatus;
use App\Http\Requests\Admin\Procurement\ReceivePurchaseOrderRequest;
use App\Http\Requests\Admin\Procurement\StorePurchaseOrderRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\Organisation\AuditLogger;
use App\Services\Procurement\PurchaseOrderLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PurchaseOrderController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.procurement.index', [
            'orders' => PurchaseOrder::query()
                ->with(['supplier:id,company_name', 'branch:id,name', 'lines'])
                ->orderByDesc('created_at')
                ->cursorPaginate(config('pagination.default', 10)),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'supplier_code']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->where('track_inventory', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(
        StorePurchaseOrderRequest $request,
        CreatePurchaseOrder $creator,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $order = $creator->execute($request, $actor);

        $this->audit->record($request, 'purchase-order.created', 'purchase_order', $order, after: [
            'order_number' => $order->order_number,
            'supplier_id' => (string) $order->supplier_id,
            'branch_id' => (string) $order->branch_id,
            'total_kobo' => $order->total_kobo,
        ]);

        return redirect()->route('admin.procurement.orders.edit', $order)
            ->with('status', 'Purchase order created. Review it before approval.');
    }

    public function edit(
        PurchaseOrder $order,
        PurchaseOrderLifecycleService $lifecycle,
    ): View {
        abort_unless($lifecycle->editable($order), 409, 'This purchase order can no longer be edited.');

        return view('admin.procurement.edit', [
            'order' => $order->load(['lines.product', 'supplier', 'branch']),
            'suppliers' => Supplier::query()->where('status', 'active')
                ->orderBy('company_name')->get(['id', 'company_name', 'supplier_code']),
            'branches' => Branch::query()->where('status', 'active')
                ->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('track_inventory', true)
                ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function update(
        Request $request,
        PurchaseOrder $order,
        PurchaseOrderLifecycleService $lifecycle,
    ): RedirectResponse {
        $validated = $request->validate([
            'supplier_id' => ['required', 'ulid'],
            'branch_id' => ['required', 'ulid'],
            'expected_at' => ['nullable', 'date'],
            'reference_note' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        /** @var Account $actor */
        $actor = $request->user();
        $order->loadMissing('lines');
        $before = [
            'supplier_id' => (string) $order->supplier_id,
            'branch_id' => (string) $order->branch_id,
            'status' => $order->status instanceof PurchaseOrderStatus ? $order->status->value : (string) $order->status,
            'total_kobo' => $order->total_kobo,
            'lines' => $order->lines->map(static fn (PurchaseOrderLine $line): array => [
                'product_id' => (string) $line->product_id,
                'ordered_quantity_milliunits' => $line->ordered_quantity_milliunits,
                'unit_cost_kobo' => $line->unit_cost_kobo,
                'tax_rate_basis_points' => $line->tax_rate_basis_points,
                'line_total_kobo' => $line->line_total_kobo,
            ])->all(),
        ];
        $updated = $lifecycle->revise($order, $actor, $validated);

        $this->audit->record($request, 'purchase-order.revised', 'purchase_order', $updated, before: $before, after: [
            'supplier_id' => (string) $updated->supplier_id,
            'branch_id' => (string) $updated->branch_id,
            'status' => PurchaseOrderStatus::Draft->value,
            'total_kobo' => $updated->total_kobo,
            'approval_reset' => true,
            'lines' => $updated->lines->map(static fn (PurchaseOrderLine $line): array => [
                'product_id' => (string) $line->product_id,
                'ordered_quantity_milliunits' => $line->ordered_quantity_milliunits,
                'unit_cost_kobo' => $line->unit_cost_kobo,
                'tax_rate_basis_points' => $line->tax_rate_basis_points,
                'line_total_kobo' => $line->line_total_kobo,
            ])->all(),
        ]);

        return redirect()->route('admin.procurement.orders.edit', $updated)
            ->with('status', 'Purchase order updated and returned to draft for approval.');
    }

    public function approve(Request $request, PurchaseOrder $order): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw new \DomainException('Only draft purchase orders can be approved.');
        }

        $order->forceFill([
            'status' => PurchaseOrderStatus::Approved,
            'approval_status' => 'approved',
            'approved_by_account_id' => $actor->getKey(),
            'approved_at' => now(),
        ])->save();

        $this->audit->record($request, 'purchase-order.approved', 'purchase_order', $order, after: [
            'status' => PurchaseOrderStatus::Approved->value,
        ]);

        return back()->with('status', 'Purchase order approved.');
    }

    public function cancel(
        Request $request,
        PurchaseOrder $order,
        PurchaseOrderLifecycleService $lifecycle,
    ): RedirectResponse {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        /** @var Account $actor */
        $actor = $request->user();
        $cancelled = $lifecycle->cancel($order, $actor, $validated['reason']);
        $this->audit->record($request, 'purchase-order.cancelled', 'purchase_order', $cancelled, after: [
            'status' => PurchaseOrderStatus::Cancelled->value,
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.procurement.orders.index')
            ->with('status', 'Purchase order cancelled without deleting its history.');
    }

    public function cancelOutstanding(
        Request $request,
        PurchaseOrder $order,
        PurchaseOrderLifecycleService $lifecycle,
    ): RedirectResponse {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        /** @var Account $actor */
        $actor = $request->user();
        $closed = $lifecycle->cancelOutstanding($order, $actor, $validated['reason']);
        $this->audit->record($request, 'purchase-order.outstanding-cancelled', 'purchase_order', $closed, after: [
            'status' => $closed->status instanceof PurchaseOrderStatus ? $closed->status->value : (string) $closed->status,
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', 'Outstanding purchase quantity cancelled. Received history was preserved.');
    }

    public function receive(
        ReceivePurchaseOrderRequest $request,
        PurchaseOrder $order,
        ReceivePurchaseOrder $receiver,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $receiver->execute(
            $order,
            $actor,
            $request->string('reference_note')->trim()->toString(),
            $request->array('lines'),
        );

        $freshOrder = $order->fresh();
        if (! $freshOrder instanceof PurchaseOrder) {
            throw new \RuntimeException('The received purchase order could not be reloaded.');
        }
        $freshStatus = $freshOrder->status instanceof PurchaseOrderStatus
            ? $freshOrder->status->value
            : (string) $freshOrder->status;

        $this->audit->record($request, 'purchase-order.received', 'purchase_order', $freshOrder, after: [
            'status' => $freshStatus,
        ]);

        return back()->with('status', 'Goods receipt recorded once through the canonical stock ledger.');
    }
}
