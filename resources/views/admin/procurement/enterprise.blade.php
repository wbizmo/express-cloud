<x-layout.app title="Enterprise Procurement | Express Cloud">
    <x-layout.app-shell
        page-title="Requisitions, receipts and landed cost"
        page-description="Approval-led procurement with partial receipts, backorders, quarantine, controlled voids and inventory capitalization."
    >
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_430px]">
            <div class="space-y-6">
                <x-ui.card title="Purchase requisitions">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead><tr class="border-b text-left text-xs uppercase text-slate-500"><th class="p-3">Number</th><th class="p-3">Warehouse</th><th class="p-3">Status</th><th class="p-3">Priority</th><th class="p-3">Actions</th></tr></thead>
                            <tbody>
                                @forelse($requisitions as $requisition)
                                    <tr class="border-b">
                                        <td class="p-3 font-mono">{{ $requisition->requisition_number }}</td>
                                        <td class="p-3">{{ $requisition->warehouse?->name }}</td>
                                        <td class="p-3">{{ ucfirst($requisition->status) }}</td>
                                        <td class="p-3">{{ ucfirst($requisition->priority) }}</td>
                                        <td class="p-3"><div class="flex flex-wrap gap-2">
                                            @if($requisition->status === 'submitted')
                                                <form method="POST" action="{{ route('admin.procurement.enterprise.requisitions.approve', $requisition) }}">
                                                    @csrf
                                                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                                    <x-ui.button type="submit" icon="check-circle">Approve</x-ui.button>
                                                </form>
                                            @endif
                                            @if($requisition->status === 'approved')
                                                <form method="POST" action="{{ route('admin.procurement.enterprise.requisitions.convert', $requisition) }}" class="flex gap-2">
                                                    @csrf
                                                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                                    <select name="supplier_id" required class="min-h-10 rounded-lg border px-2"><option value="">Supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>@endforeach</select>
                                                    <x-ui.button type="submit" icon="shopping-cart">Create PO</x-ui.button>
                                                </form>
                                            @endif
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="p-8 text-center text-slate-500">No requisitions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $requisitions->links() }}
                </x-ui.card>

                <x-ui.card title="Open purchase orders and backorders">
                    <div class="space-y-3">
                        @forelse($orders as $order)
                            <details class="ec-record-card">
                                <summary class="cursor-pointer font-semibold">{{ $order->order_number }} · {{ $order->supplier?->company_name }} · {{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</summary>
                                <div class="mt-4 space-y-3">
                                    @foreach($order->lines as $line)
                                        @if($line->remainingMilliunits() > 0)
                                            <form method="POST" action="{{ route('admin.procurement.enterprise.orders.receive', $order) }}" class="grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-4">
                                                @csrf
                                                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                                <select name="warehouse_id" required class="min-h-10 rounded-lg border px-2">@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string)$order->warehouse_id === (string)$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select>
                                                <x-ui.input name="quantity" label="Received" :value="number_format($line->remainingMilliunits()/1000, 3, '.', '')" required />
                                                <x-ui.input name="accepted_quantity" label="Accepted" :value="number_format($line->remainingMilliunits()/1000, 3, '.', '')" />
                                                <x-ui.input name="quarantine_quantity" label="Quarantine" value="0" />
                                                <x-ui.input name="batch_number" label="Batch" />
                                                <x-ui.input name="expires_on" type="date" label="Expiry" />
                                                <x-ui.button type="submit" icon="inventory">Post receipt</x-ui.button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <p class="text-sm text-slate-500">No purchase orders.</p>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card title="Goods receipts, landed cost and reversals" description="Reverse landed costs first, then void a receipt. Original documents remain in the audit trail.">
                    <div class="space-y-4">
                        @forelse($receipts as $receipt)
                            <article class="ec-record-card">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">{{ $receipt->receipt_number }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $receipt->purchaseOrder?->order_number }} · {{ $receipt->warehouse?->name }}</p>
                                    </div>
                                    <x-ui.status-badge :tone="$receipt->status === 'voided' ? 'danger' : 'success'">{{ ucfirst($receipt->status) }}</x-ui.status-badge>
                                </div>

                                @if($receipt->status !== 'voided')
                                    <form method="POST" action="{{ route('admin.procurement.enterprise.receipts.landed-cost', $receipt) }}" class="mt-4 grid gap-2 md:grid-cols-4">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                        <x-ui.input name="description" label="Cost description" required />
                                        <x-ui.input name="amount" type="number" step="0.01" label="Amount" required />
                                        <select name="allocation_method" class="min-h-11 self-end rounded-lg border px-3"><option value="value">By value</option><option value="quantity">By quantity</option><option value="equal">Equal</option></select>
                                        <x-ui.button type="submit" icon="add">Allocate landed cost</x-ui.button>
                                    </form>

                                    @foreach($receipt->landedCosts->where('status', 'active') as $allocation)
                                        <form method="POST" action="{{ route('admin.procurement.enterprise.landed-costs.reverse', $allocation) }}" class="mt-3 flex flex-wrap items-end gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                            @csrf
                                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                            <div class="mr-auto"><p class="text-sm font-semibold text-amber-950">{{ $allocation->cost_type }}</p><p class="text-xs text-amber-700">₦{{ number_format($allocation->amount_kobo/100, 2) }}</p></div>
                                            <input name="reason" required maxlength="1000" placeholder="Reversal reason" class="ec-inline-input">
                                            <x-ui.button type="submit" variant="danger" icon="refresh-cw">Reverse cost</x-ui.button>
                                        </form>
                                    @endforeach

                                    <form method="POST" action="{{ route('admin.procurement.enterprise.receipts.void', $receipt) }}" class="mt-3 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-3">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                        <input name="reason" required maxlength="1000" placeholder="Reason for voiding receipt" class="ec-inline-input min-w-[260px] flex-1">
                                        <x-ui.button type="submit" variant="danger" icon="cancel">Void receipt</x-ui.button>
                                    </form>
                                @elseif($receipt->void_reason)
                                    <p class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $receipt->void_reason }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">No goods receipts.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card title="New purchase requisition">
                <form method="POST" action="{{ route('admin.procurement.enterprise.requisitions.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                    <select name="warehouse_id" required class="min-h-11 w-full rounded-lg border px-3"><option value="">Destination warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select>
                    <select name="product_id" required class="min-h-11 w-full rounded-lg border px-3"><option value="">Product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select>
                    <x-ui.input name="quantity" label="Requested quantity" required />
                    <x-ui.input name="estimated_unit_cost" type="number" step="0.01" label="Estimated unit cost" />
                    <select name="priority" class="min-h-11 w-full rounded-lg border px-3"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option><option value="low">Low</option></select>
                    <x-ui.input name="needed_on" type="date" label="Needed on" />
                    <label class="block"><span class="mb-2 block text-sm font-medium">Reason</span><textarea name="reason" required class="ec-textarea"></textarea></label>
                    <x-ui.button type="submit" icon="add" class="w-full">Submit requisition</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
