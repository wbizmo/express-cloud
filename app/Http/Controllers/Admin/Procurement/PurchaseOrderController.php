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
use App\Models\Supplier;
use App\Services\Organisation\AuditLogger;
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
                ->with(['supplier:id,company_name', 'branch:id,name'])
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

        $this->audit->record(
            $request,
            'purchase-order.created',
            'purchase_order',
            $order,
            after: [
                'order_number' => $order->order_number,
                'supplier_id' => (string) $order->supplier_id,
                'branch_id' => (string) $order->branch_id,
                'total_kobo' => $order->total_kobo,
            ],
        );

        return back()->with('status', 'Purchase order created.');
    }

    public function approve(
        Request $request,
        PurchaseOrder $order,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw new \DomainException(
                'Only draft purchase orders can be approved.',
            );
        }

        $order->forceFill([
            'status' => PurchaseOrderStatus::Approved,
            'approved_by_account_id' => $actor->getKey(),
            'approved_at' => now(),
        ])->save();

        $this->audit->record(
            $request,
            'purchase-order.approved',
            'purchase_order',
            $order,
            after: ['status' => PurchaseOrderStatus::Approved->value],
        );

        return back()->with('status', 'Purchase order approved.');
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
            throw new \RuntimeException(
                'The received purchase order could not be reloaded.',
            );
        }

        $freshStatus = $freshOrder->status instanceof PurchaseOrderStatus
            ? $freshOrder->status->value
            : (string) $freshOrder->status;

        $this->audit->record(
            $request,
            'purchase-order.received',
            'purchase_order',
            $freshOrder,
            after: [
                'status' => $freshStatus,
            ],
        );

        return back()->with('status', 'Goods receipt recorded.');
    }
}
