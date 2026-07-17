<x-layout.app title="Products | Express Cloud">
    <x-layout.app-shell
        page-title="Products"
        page-description="Manage product identity and pricing without modifying stock history."
    >
        <x-slot:actions>
            <a href="{{ route('admin.catalog.products.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                <x-ui.icon name="plus" :size="17" />
                New product
            </a>
        </x-slot:actions>

        <x-ui.card title="Product catalogue">
            <div class="overflow-x-auto">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-10 text-center text-slate-500">
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
