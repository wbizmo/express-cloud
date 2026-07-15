<x-layout.app title="Low stock | Express Cloud">
    <x-layout.app-shell
        page-title="Low-stock alerts"
        page-description="Open alerts for tracked products at or below their branch minimum."
    >
        <x-ui.card title="Products requiring attention">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Product</th>
                            <th class="px-3 py-3">Branch</th>
                            <th class="px-3 py-3">Quantity</th>
                            <th class="px-3 py-3">Minimum</th>
                            <th class="px-3 py-3">Last seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($alerts as $alert)
                            <tr>
                                <td class="px-3 py-4">
                                    <p class="font-medium text-slate-950">{{ $alert->product?->name }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $alert->product?->sku }}</p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ $alert->branch?->name }}</td>
                                <td class="px-3 py-4 font-semibold text-red-700">{{ $quantity->format($alert->quantity_milliunits) }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $quantity->format($alert->minimum_stock_milliunits) }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $alert->last_seen_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-10 text-center text-slate-500">
                                    No open low-stock alerts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
