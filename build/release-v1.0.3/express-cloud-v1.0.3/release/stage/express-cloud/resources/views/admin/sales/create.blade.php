<x-layout.app title="New sale | Express Cloud">
    <x-layout.app-shell
        page-title="New sale"
        page-description="Create an invoice, quote, or POS transaction with barcode-ready item entry."
    >
        <form method="POST" action="{{ route('admin.sales.store') }}" class="space-y-6" x-data="{ lines: [0], payments: [0] }">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

            <x-ui.card title="Sale details">
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Sale type</span>
                        <select name="sale_type" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <option value="invoice">Invoice</option>
                            <option value="quote">Quote</option>
                            <option value="pos">POS</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Branch</span>
                        <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Customer</span>
                        <input
                            type="search"
                            placeholder="Search customer or leave blank for walk-in"
                            class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm"
                            data-customer-search
                        >
                        <input type="hidden" name="customer_id" data-customer-id>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Items">
                <div class="space-y-4">
                    <template x-for="index in lines" :key="index">
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                            <select :name="`items[${index}][product_id]`" required class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Product or barcode</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} — {{ $product->sku }}{{ $product->barcode ? ' — '.$product->barcode : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input :name="`items[${index}][quantity]`" value="1" required class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Quantity">
                            <input :name="`items[${index}][unit_price]`" type="number" step="0.01" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Price override">
                            <input :name="`items[${index}][discount]`" type="number" step="0.01" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Discount">
                            <button type="button" x-on:click="lines = lines.filter((line) => line !== index)" class="text-sm font-semibold text-red-700">Remove</button>
                        </div>
                    </template>
                </div>
                <button type="button" x-on:click="lines.push(lines.length ? Math.max(...lines) + 1 : 0)" class="mt-4 text-sm font-semibold text-blue-700">
                    Add another item
                </button>
            </x-ui.card>

            <x-ui.card title="Payments" description="Quotes ignore payments until converted. Invoices may be unpaid or partially paid.">
                <div class="space-y-4">
                    <template x-for="index in payments" :key="index">
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-3">
                            <select :name="`payments[${index}][payment_method_id]`" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Payment method</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <input :name="`payments[${index}][amount]`" type="number" step="0.01" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Amount">
                            <input :name="`payments[${index}][reference]`" class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Reference">
                        </div>
                    </template>
                </div>
                <button type="button" x-on:click="payments.push(payments.length)" class="mt-4 text-sm font-semibold text-blue-700">
                    Add split payment
                </button>
            </x-ui.card>

            <x-ui.card title="Notes">
                <textarea name="notes" class="min-h-28 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="submit">Complete sale</x-ui.button>
            </div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
