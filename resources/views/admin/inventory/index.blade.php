<x-layout.app title="Inventory | Express Cloud">
    <x-layout.app-shell
        page-title="Inventory by branch"
        page-description="Current balances are derived transactionally from the append-only stock ledger."
    >
        <x-slot:actions>
            @can('products.prices.adjust')
                <a href="{{ route('admin.catalog.price-adjustments.index') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <x-ui.icon name="badge-dollar-sign" :size="17" />
                    Bulk price update
                </a>
            @endcan
        </x-slot:actions>
        <div class="ec-inventory-split grid min-w-0 gap-6 2xl:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
            <x-ui.card title="Branch stock">
                <div class="ec-responsive-table overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Product</th>
                                <th class="px-3 py-3">Branch</th>
                                <th class="px-3 py-3">Quantity</th>
                                <th class="px-3 py-3">Minimum</th>
                                <th class="px-3 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($stocks as $stock)
                                <tr>
                                    <td class="px-3 py-4">
                                        <p class="font-medium text-slate-950">{{ $stock->product?->name }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $stock->product?->sku }}</p>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">{{ $stock->branch?->name }}</td>
                                    <td class="px-3 py-4 font-semibold text-slate-950">{{ app(\App\Services\Inventory\Quantity::class)->format($stock->quantity_milliunits) }}</td>
                                    <td class="px-3 py-4 text-slate-600">{{ app(\App\Services\Inventory\Quantity::class)->format($stock->minimum_stock_milliunits) }}</td>
                                    <td class="px-3 py-4">
                                        <x-ui.status-badge :tone="$stock->isLowStock() ? 'warning' : 'success'">
                                            {{ $stock->isLowStock() ? 'Low stock' : 'Healthy' }}
                                        </x-ui.status-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-10 text-center text-slate-500">
                                        No stock balances recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="min-w-0 space-y-6">
                <x-ui.card title="Stock intake">
                    <form method="POST" action="{{ route('admin.inventory.intake') }}" class="space-y-4">
                        @csrf
                        <div data-product-finder class="relative">
                            <input type="search" data-product-query autocomplete="off" placeholder="Scan barcode or type product name / SKU" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input type="hidden" data-product-id name="product_id" required>
                            <div data-product-results hidden class="ec-product-results"></div>
                            <script type="application/json" data-products-json>@json($products)</script>
                        </div>
                        <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <x-ui.input name="quantity" label="Quantity" required />
                        <x-ui.input name="unit_cost" type="number" step="0.01" label="Unit cost (₦)" />
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                            <textarea name="reference_note" required class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                        </label>
                        <x-ui.button type="submit" class="w-full">Record intake</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card title="Stock adjustment">
                    <form method="POST" action="{{ route('admin.inventory.adjust') }}" class="space-y-4">
                        @csrf
                        <div data-product-finder class="relative">
                            <input type="search" data-product-query autocomplete="off" placeholder="Scan barcode or type product name / SKU" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input type="hidden" data-product-id name="product_id" required>
                            <div data-product-results hidden class="ec-product-results"></div>
                            <script type="application/json" data-products-json>@json($products)</script>
                        </div>
                        <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <x-ui.input name="quantity_delta" label="Quantity change" help="Use a negative value to reduce stock." required />
                        <select name="reason_code" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <option value="">Select reason</option>
                            @foreach ($reasons as $reason)
                                <option value="{{ $reason->value }}">{{ ucfirst($reason->value) }}</option>
                            @endforeach
                        </select>
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                            <textarea name="reference_note" required class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                        </label>
                        <x-ui.button type="submit" class="w-full">Record adjustment</x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
