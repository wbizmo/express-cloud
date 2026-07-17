<x-layout.app title="Supplier bills | Express Cloud">
    <x-layout.app-shell
        page-title="Supplier bills"
        page-description="Record supplier invoices, due dates, payments, purchase-order references, and supporting documents."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_460px]">
            <x-ui.card title="Bill history">
                <div class="space-y-3">
                    @forelse ($bills as $bill)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <a
                                        href="{{ route('admin.supplier-finance.bills.show', $bill) }}"
                                        class="font-semibold text-blue-700 hover:underline"
                                    >
                                        {{ $bill->bill_number }}
                                    </a>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $bill->supplier?->company_name }} · {{ $bill->branch?->name }}
                                    </p>
                                </div>
                                <x-ui.status-badge :tone="$bill->status->value === 'paid' ? 'success' : 'info'">
                                    {{ ucfirst($bill->status->value) }}
                                </x-ui.status-badge>
                            </div>
                            <div class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                                <p>Total: ₦{{ number_format($bill->total_kobo / 100, 2) }}</p>
                                <p>Paid: ₦{{ number_format($bill->paid_kobo / 100, 2) }}</p>
                                <p>Due: {{ $bill->due_date?->format('d M Y') ?? 'Not set' }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">
                            No supplier bills recorded.
                        </p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create supplier bill">
                <form
                    method="POST"
                    action="{{ route('admin.supplier-finance.bills.store') }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                    x-data="{ lines: [0] }"
                >
                    @csrf

                    <select name="supplier_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">
                                {{ $supplier->company_name }} — {{ $supplier->supplier_code }}
                            </option>
                        @endforeach
                    </select>

                    <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Select branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>

                    <select name="purchase_order_id" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">No linked purchase order</option>
                        @foreach ($purchaseOrders as $order)
                            <option value="{{ $order->id }}">{{ $order->order_number }}</option>
                        @endforeach
                    </select>

                    <div class="grid gap-4 sm:grid-cols-[repeat(2,minmax(0,1fr))]">
                        <x-ui.input name="bill_date" type="date" label="Bill date" required />
                        <x-ui.input name="due_date" type="date" label="Due date" />
                    </div>

                    <x-ui.input name="supplier_reference" label="Supplier invoice reference" />

                    <template x-for="index in lines" :key="index">
                        <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                            <select :name="`lines[${index}][product_id]`" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Non-product expense</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                            <input :name="`lines[${index}][description]`" required placeholder="Description" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <input :name="`lines[${index}][quantity]`" value="1" required placeholder="Quantity" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                                <input :name="`lines[${index}][unit_cost]`" type="number" step="0.01" required placeholder="Unit cost ₦" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                                <input :name="`lines[${index}][tax_rate_percent]`" type="number" step="0.01" placeholder="Tax %" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                            </div>
                        </div>
                    </template>

                    <button
                        type="button"
                        x-on:click="lines.push(lines.length ? Math.max(...lines) + 1 : 0)"
                        class="text-sm font-semibold text-blue-700"
                    >
                        Add another line
                    </button>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                        <textarea name="reference_note" required class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Supporting document</span>
                        <input
                            type="file"
                            name="attachment"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.docx"
                            class="block w-full rounded-lg border border-slate-300 p-3 text-sm"
                        >
                    </label>

                    <x-ui.input name="attachment_description" label="Document description" />

                    <x-ui.button type="submit" class="w-full">
                        Create supplier bill
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
