<x-layout.app title="Edit {{ $sale->sale_code }} | Express Cloud">
    <x-layout.app-shell
        page-title="Edit / Reissue {{ $sale->sale_code }}"
        page-description="Saving this creates a new invoice with your changes and voids the original — the original's stock and accounting entries are automatically reversed."
    >
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            This does not edit {{ $sale->sale_code }} in place. It voids it (reversing stock and the posted journal entry) and records a brand-new sale from what you submit below, linked back to this one for traceability.
        </div>

        <form method="POST" action="{{ route('admin.sales.update', $sale) }}" x-data="{
            items: {{ Illuminate\Support\Js::from($sale->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity' => (string) ($i->quantity_milliunits / 1000),
                'unit_price' => (string) ($i->unit_price_kobo / 100),
                'discount' => (string) ($i->discount_amount_kobo / 100),
            ])) }},
            addLine() { this.items.push({ product_id: '', quantity: '1', unit_price: '', discount: '0' }); },
            removeLine(i) { this.items.splice(i, 1); },
        }">
            @csrf
            @method('PUT')
            <input type="hidden" name="idempotency_key" value="{{ (string) Str::uuid() }}">
            <input type="hidden" name="sale_type" value="{{ $sale->sale_type->value }}">

            <div class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Branch</span>
                    <x-ui.searchable-select name="branch_id" :options="$branches->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])" :selected="$sale->branch_id" required />
                </label>
                <x-ui.input name="reissue_reason" label="Reason for this change" placeholder="e.g. Wrong item, price correction…" required />
                @if ($sale->customer_id)
                    <input type="hidden" name="customer_id" value="{{ $sale->customer_id }}">
                @endif
            </div>

            <x-ui.card title="Line items">
                <template x-for="(item, index) in items" :key="index">
                    <div class="mb-3 grid gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-5">
                        <label class="block md:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Product</span>
                            <select :name="'items['+index+'][product_id]'" x-model="item.product_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Qty</span>
                            <input type="text" :name="'items['+index+'][quantity]'" x-model="item.quantity" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Unit price (₦)</span>
                            <input type="text" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                        </label>
                        <div class="flex items-end gap-2">
                            <label class="block flex-1">
                                <span class="mb-1 block text-xs font-medium text-slate-500">Discount (₦)</span>
                                <input type="text" :name="'items['+index+'][discount]'" x-model="item.discount" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                            </label>
                            <button type="button" x-on:click="removeLine(index)" class="min-h-11 rounded-lg border border-red-300 px-3 text-sm text-red-700 hover:bg-red-50">Remove</button>
                        </div>
                    </div>
                </template>

                <button type="button" x-on:click="addLine()" class="mt-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">+ Add line item</button>
            </x-ui.card>

            <div class="mt-6 flex items-center gap-3">
                <x-ui.button type="submit">Save as new invoice &amp; void the original</x-ui.button>
                <a href="{{ route('admin.sales.show', $sale) }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
