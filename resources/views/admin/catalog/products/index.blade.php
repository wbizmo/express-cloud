                    <x-layout.app title="Products | Express Cloud">
                        <x-layout.app-shell
                            page-title="Products"
                            page-description="Manage product identity and pricing without modifying stock history."
                        >
                            <x-slot:actions>
                                @can('products.prices.adjust')
                                    <a href="{{ route('admin.catalog.price-adjustments.index') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        <x-ui.icon name="badge-dollar-sign" :size="17" />
                                        Bulk price update
                                    </a>
                                @endcan
                                @can('categories.manage')
                                    <a href="{{ route('admin.catalog.categories.index') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Categories</a>
                                @endcan
                                @can('brands.manage')
                                    <a href="{{ route('admin.catalog.brands.index') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Brands</a>
                                @endcan
                                <a href="{{ route('admin.catalog.products.create') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                                    <x-ui.icon name="plus" :size="17" />
                                    New product
                                </a>
                            </x-slot:actions>

                            <x-ui.card title="Product catalogue">
                                <div class="ec-responsive-table overflow-x-auto">
                                    <table class="w-full min-w-[920px] text-left text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                                <th class="px-3 py-3">Product</th>
                                                <th class="px-3 py-3">SKU</th>
                                                <th class="px-3 py-3">Category</th>
                                                <th class="px-3 py-3">Brand</th>
                                                <th class="px-3 py-3">Price</th>
                                                <th class="px-3 py-3">Inventory</th>
                                                <th class="px-3 py-3">Status</th>
                                                <th class="px-3 py-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($products as $product)
                                                <tr>
                                                    <td class="px-3 py-4 font-medium text-slate-950">{{ $product->name }}</td>
                                                    <td class="px-3 py-4 font-mono text-xs text-slate-600">{{ $product->sku }}</td>
                                                    <td class="px-3 py-4 text-slate-600">{{ $product->category?->name }}</td>
                                                    <td class="px-3 py-4 text-slate-600">{{ $product->brand?->name ?? '—' }}</td>
                                                    <td class="px-3 py-4 font-medium text-slate-950">₦{{ number_format($product->default_price_kobo / 100, 2) }}</td>
                                                    <td class="px-3 py-4">
                                                        <x-ui.status-badge :tone="$product->track_inventory ? 'info' : 'neutral'">
                                                            {{ $product->inventoryLabel() }}
                                                        </x-ui.status-badge>
                                                    </td>
                                                    <td class="px-3 py-4">
                                                        <x-ui.status-badge :tone="$product->status->value === 'active' ? 'success' : 'neutral'">
                                                            {{ ucfirst($product->status->value) }}
                                                        </x-ui.status-badge>
                                                    </td>
                                                    <td class="px-3 py-4 text-right">
                                                        {{-- Edit button – moderately compact with ~7px all-round padding --}}
                                                        <a
                                                            href="{{ route('admin.catalog.products.edit', $product->id) }}"
                                                            class="inline-flex min-h-7 items-center rounded-md bg-slate-900 px-2 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1"
                                                        >
                                                            Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="px-3 py-10 text-center text-slate-500">
                                                        No products configured.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </x-ui.card>
                        </x-layout.app-shell>
                    </x-layout.app>