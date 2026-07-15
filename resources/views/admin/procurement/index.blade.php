<x-layout.app title="Purchase orders | Express Cloud">
    <x-layout.app-shell
        page-title="Purchase orders"
        page-description="Create supplier orders and receive goods into branch inventory through the same stock ledger."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_440px]">
            <x-ui.card title="Purchase-order history">
                <div class="space-y-3">
                    @forelse ($orders as $order)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="font-semibold text-slate-950">{{ $order->order_number }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $order->supplier?->company_name }} · {{ $order->branch?->name }}
                                    </p>
                                </div>
                                <x-ui.status-badge :tone="$order->status->value === 'received' ? 'success' : 'info'">
                                    {{ str_replace('_', ' ', ucfirst($order->status->value)) }}
                                </x-ui.status-badge>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <span class="text-slate-500">{{ $order->created_at?->format('d M Y') }}</span>
                                <span class="font-semibold text-slate-950">₦{{ number_format($order->total_kobo / 100, 2) }}</span>
                            </div>
                            @if ($order->status->value === 'draft')
                                <form method="POST" action="{{ route('admin.procurement.orders.approve', $order) }}" class="mt-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="secondary">Approve order</x-ui.button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No purchase orders.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create purchase order">
                <form method="POST" action="{{ route('admin.procurement.orders.store') }}" class="space-y-4" x-data="{ lines: [0] }">
                    @csrf
                    <select name="supplier_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->company_name }} — {{ $supplier->supplier_code }}</option>
                        @endforeach
                    </select>
                    <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Receiving branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.input name="expected_at" type="date" label="Expected date" />
                    <template x-for="index in lines" :key="index">
                        <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                            <select :name="`lines[${index}][product_id]`" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>
                                @endforeach
                            </select>
                            <input :name="`lines[${index}][quantity]`" required placeholder="Quantity" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input :name="`lines[${index}][unit_cost]`" type="number" step="0.01" required placeholder="Unit cost (₦)" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input :name="`lines[${index}][tax_rate_percent]`" type="number" step="0.01" placeholder="Tax rate %" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        </div>
                    </template>
                    <button type="button" x-on:click="lines.push(lines.length)" class="text-sm font-semibold text-blue-700">
                        Add another line
                    </button>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                        <textarea name="reference_note" required class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                    </label>
                    <x-ui.button type="submit" class="w-full">Create purchase order</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
