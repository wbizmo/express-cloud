<x-layout.app title="Record purchase | Express Cloud">
<x-layout.app-shell page-title="Record purchase" page-description="Only products already in the catalogue can be purchased.">
<div data-page-header class="mb-5"></div>
<form method="POST" action="{{ route('admin.commercial.purchases.store') }}" class="space-y-6" data-commercial-lines>
@csrf
<x-ui.card title="Purchase details">
<div class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-4">
<label><span class="mb-2 block text-sm font-medium">Supplier</span><x-ui.searchable-select name="supplier_id" :options="$suppliers->map(fn ($s) => ['value' => $s->id, 'label' => $s->company_name.($s->supplier_code ? ' · '.$s->supplier_code : '')])" placeholder="Select supplier" required /></label>
<label><span class="mb-2 block text-sm font-medium">Receiving branch</span><x-ui.searchable-select name="branch_id" :options="$branches->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])" placeholder="Select branch" required /></label>
<x-ui.input name="purchased_at" type="date" label="Purchase date" :value="today()->toDateString()" required />
<x-ui.input name="supplier_reference" label="Supplier reference" />
</div>
</x-ui.card>
<x-ui.card title="Products">
<div id="purchase-lines" class="space-y-3">
<div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
<label><span class="mb-2 block text-xs font-semibold uppercase text-slate-500">Product</span><select name="lines[0][product_id]" required><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></label>
<x-ui.input name="lines[0][quantity]" type="number" step="0.001" label="Quantity" required />
<x-ui.input name="lines[0][unit_cost]" type="number" step="0.01" label="Unit cost" required />
<x-ui.input name="lines[0][discount]" type="number" step="0.01" label="Discount" />
<x-ui.input name="lines[0][tax]" type="number" step="0.01" label="Tax" />
<button type="button" class="self-end rounded-lg border border-slate-300 px-3 py-2" data-remove-line>Remove</button>
</div>
</div>
<button type="button" class="mt-4 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" data-add-line>Add another product</button>
</x-ui.card>
<x-ui.card title="Notes"><textarea name="notes" rows="4" class="w-full rounded-lg border border-slate-300 p-3"></textarea></x-ui.card>
<x-ui.button type="submit">Record purchase and update stock</x-ui.button>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('purchase-lines');
    const template = container.firstElementChild.outerHTML;
    let index = 1;
    document.querySelector('[data-add-line]').addEventListener('click', () => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.replaceAll('[0]', `[${index}]`);
        container.appendChild(wrapper.firstElementChild);
        index += 1;
    });
    container.addEventListener('click', (event) => {
        if (!event.target.matches('[data-remove-line]')) return;
        if (container.children.length === 1) return;
        event.target.closest('.grid').remove();
    });
});
</script>
</x-layout.app-shell>
</x-layout.app>
