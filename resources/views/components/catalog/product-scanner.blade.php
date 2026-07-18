@props([
    'name' => 'product_id',
    'branchField' => 'branch_id',
    'context' => 'sale',
    'label' => 'Scan barcode or search product',
    'required' => true,
    'showAvailability' => true,
])
<div
    class="ec-product-scanner min-w-0"
    x-data="productScanner({
        endpoint: @js(route('catalog.products.lookup')),
        context: @js($context),
        branchField: @js($branchField),
    })"
    x-on:keydown.escape.window="close()"
>
    <label class="block min-w-0">
        <span class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</span>
        <input type="hidden" name="{{ $name }}" x-model="selectedId" @required($required)>
        <input
            type="search"
            x-model="term"
            x-bind:disabled="!branchId()"
            x-bind:placeholder="branchId() ? 'Scan barcode or search product name / SKU' : 'Select a branch to enable product search'"
            x-on:input.debounce.180ms="search()"
            x-on:keydown.enter.prevent="chooseExactOrFirst()"
            x-on:focus="term && search()"
            autocomplete="off"
            inputmode="search"
            class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"
        >
    </label>
    <div x-show="open" x-cloak class="relative z-30 mt-1 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
        <template x-for="item in results" :key="item.id">
            <button type="button" x-on:click="select(item)" class="flex w-full min-w-0 items-start justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-0 hover:bg-slate-50">
                <span class="min-w-0">
                    <strong class="block truncate text-sm text-slate-900" x-text="item.name"></strong>
                    <span class="block truncate text-xs text-slate-500" x-text="[item.sku, item.barcode].filter(Boolean).join(' · ')"></span>
                </span>
                @if ($showAvailability)
                    <span class="shrink-0 text-right text-xs text-slate-600">
                        <span class="block font-semibold" x-text="`₦${Number(item.price).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                        <span class="block" x-text="item.track_inventory ? `${item.quantity} available` : 'Untracked'"></span>
                    </span>
                @endif
            </button>
        </template>
        <p x-show="loading" class="p-3 text-sm text-slate-500">Searching products…</p>
        <p x-show="!loading && results.length === 0" class="p-3 text-sm text-slate-500">No matching product in this branch.</p>
    </div>
    <p x-show="!branchId()" class="mt-2 text-xs font-medium text-amber-700">Choose a branch above. The barcode scanner and product search will activate immediately.</p>
    <p x-show="selected" class="mt-2 truncate text-xs text-slate-600" x-text="selected ? `${selected.name} · ${selected.sku}` : ''"></p>
</div>
