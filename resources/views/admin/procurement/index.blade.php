<x-layout.app title="Purchase orders | Express Cloud">
    <x-layout.app-shell
        page-title="Purchase orders"
        page-description="Create, revise, approve, receive and close supplier orders with preserved audit history."
    >
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_440px]">
            <x-ui.card title="Purchase-order history" description="Draft and unreceived approved orders remain editable. Received quantities are immutable.">
                <div class="space-y-3">
                    @forelse ($orders as $order)
                        @php
                            $status = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;
                            $hasReceipts = $order->lines->contains(fn ($line) => $line->received_quantity_milliunits > 0);
                            $outstanding = $order->lines->sum(fn ($line) => $line->remainingMilliunits());
                        @endphp
                        <article class="ec-record-card">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="ec-record-icon"><x-ui.icon name="shopping-cart" :size="18" /></span>
                                        <h2 class="truncate font-semibold text-slate-950">{{ $order->order_number }}</h2>
                                        <x-ui.status-badge :tone="in_array($status, ['received'], true) ? 'success' : (str_contains($status, 'cancelled') ? 'danger' : 'info')">
                                            {{ str_replace('_', ' ', ucfirst($status)) }}
                                        </x-ui.status-badge>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">
                                        {{ $order->supplier?->company_name }} · {{ $order->branch?->name }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                                        <span>{{ $order->lines->count() }} line(s)</span>
                                        <span>Outstanding {{ number_format($outstanding / 1000, 3) }}</span>
                                        <span>{{ $order->created_at?->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Order total</p>
                                    <p class="mt-1 text-lg font-bold text-slate-950">₦{{ number_format($order->total_kobo / 100, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-4">
                                @if (in_array($status, ['draft', 'approved'], true) && ! $hasReceipts)
                                    <a href="{{ route('admin.procurement.orders.edit', $order) }}" class="ec-action-link">
                                        <x-ui.icon name="edit" :size="17" />
                                        <span>Edit details</span>
                                    </a>
                                @endif

                                @if ($status === 'draft')
                                    <form method="POST" action="{{ route('admin.procurement.orders.approve', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.button type="submit" variant="secondary" icon="check-circle">Approve</x-ui.button>
                                    </form>
                                @endif

                                @if (in_array($status, ['draft', 'approved'], true) && ! $hasReceipts)
                                    <form method="POST" action="{{ route('admin.procurement.orders.cancel', $order) }}" class="flex min-w-[250px] flex-1 gap-2">
                                        @csrf
                                        <input name="reason" required maxlength="1000" placeholder="Cancellation reason" class="ec-inline-input">
                                        <x-ui.button type="submit" variant="danger" icon="cancel">Cancel</x-ui.button>
                                    </form>
                                @elseif (in_array($status, ['approved', 'partially_received'], true) && $outstanding > 0)
                                    <form method="POST" action="{{ route('admin.procurement.orders.cancel-outstanding', $order) }}" class="flex min-w-[290px] flex-1 gap-2">
                                        @csrf
                                        <input name="reason" required maxlength="1000" placeholder="Reason for closing outstanding quantity" class="ec-inline-input">
                                        <x-ui.button type="submit" variant="danger" icon="block">Close outstanding</x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="ec-empty-state">
                            <x-ui.icon name="inventory" :size="30" />
                            <h3>No purchase orders yet</h3>
                            <p>Create the first supplier order from the form beside this list.</p>
                        </div>
                    @endforelse
                </div>
                <div class="mt-5">{{ $orders->links() }}</div>
            </x-ui.card>

            <x-ui.card title="Create purchase order" description="New orders begin as drafts and can be revised before approval.">
                <form method="POST" action="{{ route('admin.procurement.orders.store') }}" class="space-y-4" x-data="{ lines: [0] }">
                    @csrf
                    <x-ui.searchable-select name="supplier_id" :options="$suppliers->map(fn ($s) => ['value' => $s->id, 'label' => $s->company_name.' — '.$s->supplier_code])" placeholder="Select supplier" required />
                    <x-ui.searchable-select name="branch_id" :options="$branches->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])" placeholder="Receiving branch" required />
                    <x-ui.input name="expected_at" type="date" label="Expected date" />

                    <template x-for="index in lines" :key="index">
                        <div class="ec-line-editor">
                            <select :name="`lines[${index}][product_id]`" required>
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>
                                @endforeach
                            </select>
                            <input :name="`lines[${index}][quantity]`" required placeholder="Quantity">
                            <input :name="`lines[${index}][unit_cost]`" type="number" step="0.01" required placeholder="Unit cost (₦)">
                            <input :name="`lines[${index}][tax_rate_percent]`" type="number" step="0.01" placeholder="Tax rate %">
                            <button type="button" class="ec-icon-button danger" x-show="lines.length > 1" x-on:click="lines = lines.filter((value) => value !== index)" aria-label="Remove line">
                                <x-ui.icon name="delete" :size="18" />
                            </button>
                        </div>
                    </template>

                    <button type="button" x-on:click="lines.push(Math.max(...lines) + 1)" class="ec-action-link">
                        <x-ui.icon name="add" :size="17" />
                        <span>Add another line</span>
                    </button>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                        <textarea name="reference_note" required class="ec-textarea"></textarea>
                    </label>
                    <x-ui.button type="submit" icon="add-shopping-cart" class="w-full">Create draft purchase order</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
