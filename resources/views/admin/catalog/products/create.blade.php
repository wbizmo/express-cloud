<x-layout.app title="New product | Express Cloud">
    <x-layout.app-shell
        page-title="New product"
        page-description="Create product identity and pricing. Opening stock is recorded separately in the inventory sprint."
    >
        <form method="POST" action="{{ route('admin.catalog.products.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <x-ui.card title="Identity">
                <div class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))]">
                    <x-ui.input name="name" label="Product name" required />
                    <x-ui.input name="sku" label="SKU" required />
                    <x-ui.input name="barcode" inputmode="numeric" autocomplete="off" data-barcode-input inputmode="numeric" autocomplete="off" data-barcode-input label="Barcode" help="A connected scanner may type directly into this field." />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Category</span>
                        <select name="category_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Brand</span>
                        <select name="brand_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Product image</span>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-600">
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                    <textarea name="description" class="min-h-28 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm"></textarea>
                </label>
            </x-ui.card>

            <x-ui.card title="Inventory behaviour">
                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                    <input type="checkbox" name="track_inventory" value="1" checked class="mt-0.5 rounded border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-950">Track inventory</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-500">
                            Turn this off for services, delivery fees, framing labour, gift wrapping, or other invoiceable items that must never carry stock quantity.
                        </span>
                    </span>
                </label>
            </x-ui.card>

            <x-ui.card title="Pricing">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input name="default_price" type="number" step="0.01" label="Default selling price (₦)" required />
                    <x-ui.input name="default_cost_price" type="number" step="0.01" label="Default cost price (₦)" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Tax rate</span>
                        <select name="tax_rate_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No tax rate</option>
                            @foreach ($taxRates as $taxRate)
                                <option value="{{ $taxRate->id }}">
                                    {{ $taxRate->name }} ({{ number_format($taxRate->rate_basis_points / 100, 2) }}%)
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card
                title="Branch price overrides"
                description="Leave a branch blank to use the default selling price."
            >
                <div class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-3">
                    @foreach ($branches as $index => $branch)
                        <input type="hidden" name="branch_prices[{{ $index }}][branch_id]" value="{{ $branch->id }}">
                        <x-ui.input
                            name="branch_prices[{{ $index }}][price]"
                            type="number"
                            step="0.01"
                            :label="$branch->name"
                        />
                    @endforeach
                </div>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="submit">Create product</x-ui.button>
            </div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
