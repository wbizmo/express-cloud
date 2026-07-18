<x-layout.app title="Edit product | Express Cloud">
    <x-layout.app-shell page-title="Edit product" page-description="Update product identity, pricing, inventory behaviour and availability status.">
        <form method="POST" action="{{ route('admin.catalog.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card title="Identity">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input name="name" label="Product name" :value="old('name', $product->name)" required />
                    <x-ui.input name="sku" label="SKU" :value="old('sku', $product->sku)" required />
                    <x-ui.input name="barcode" label="Barcode" :value="old('barcode', $product->barcode)" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Category</span>
                        <select name="category_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Brand</span>
                        <select name="brand_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Replace product image</span>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-600">
                    </label>
                </div>
                <label class="mt-4 block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                    <textarea name="description" class="min-h-28 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm">{{ old('description', $product->description) }}</textarea>
                </label>
            </x-ui.card>

            <x-ui.card title="Pricing and tax">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input name="default_price" type="number" step="0.01" label="Default selling price (₦)" :value="old('default_price', number_format($product->default_price_kobo / 100, 2, '.', ''))" required />
                    <x-ui.input name="default_cost_price" type="number" step="0.01" label="Default cost price (₦)" :value="old('default_cost_price', number_format($product->default_cost_price_kobo / 100, 2, '.', ''))" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Tax rate</span>
                        <select name="tax_rate_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No tax rate</option>
                            @foreach ($taxRates as $taxRate)
                                <option value="{{ $taxRate->id }}" @selected(old('tax_rate_id', $product->tax_rate_id) === $taxRate->id)>{{ $taxRate->name }} ({{ number_format($taxRate->rate_basis_points / 100, 2) }}%)</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Inventory and status">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                        <input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', $product->track_inventory)) class="mt-0.5 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Track inventory</span>
                            <span class="mt-1 block text-sm text-slate-500">Disable this for services and non-stock sale items.</span>
                        </span>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Status</span>
                        <select name="status" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="active" @selected(old('status', $product->status->value) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $product->status->value) === 'inactive')>Inactive</option>
                        </select>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Branch price overrides" description="Leave a branch blank to use the default selling price.">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($branches as $index => $branch)
                        @php $branchKobo = $branchPriceMap->get((string) $branch->id); @endphp
                        <input type="hidden" name="branch_prices[{{ $index }}][branch_id]" value="{{ $branch->id }}">
                        <x-ui.input
                            name="branch_prices[{{ $index }}][price]"
                            type="number"
                            step="0.01"
                            :label="$branch->name"
                            :value="old('branch_prices.'.$index.'.price', $branchKobo !== null ? number_format($branchKobo / 100, 2, '.', '') : '')"
                        />
                    @endforeach
                </div>
            </x-ui.card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.catalog.products.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Cancel</a>
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
