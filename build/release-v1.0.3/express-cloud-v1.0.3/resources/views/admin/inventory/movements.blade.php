<x-layout.app title="Stock movements | Express Cloud">
    <x-layout.app-shell
        page-title="Stock movements"
        page-description="Immutable inventory history ordered by event time."
    >
        <x-ui.card title="Movement ledger">
            <div class="ec-responsive-table overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Time</th>
                            <th class="px-3 py-3">Product</th>
                            <th class="px-3 py-3">Branch</th>
                            <th class="px-3 py-3">Type</th>
                            <th class="px-3 py-3">Change</th>
                            <th class="px-3 py-3">Balance</th>
                            <th class="px-3 py-3">Actor</th>
                            <th class="px-3 py-3">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="px-3 py-4 text-slate-600">{{ $movement->occurred_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-4">
                                    <p class="font-medium text-slate-950">{{ $movement->product?->name }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $movement->product?->sku }}</p>
                                </td>
                                <td class="px-3 py-4 text-slate-600">{{ $movement->branch?->name }}</td>
                                <td class="px-3 py-4">{{ str_replace('_', ' ', ucfirst($movement->movement_type->value)) }}</td>
                                <td class="px-3 py-4 font-semibold {{ $movement->quantity_delta_milliunits < 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                    {{ $quantity->format($movement->quantity_delta_milliunits) }}
                                </td>
                                <td class="px-3 py-4 text-slate-950">{{ $quantity->format($movement->balance_after_milliunits) }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $movement->account?->displayName() ?? 'System' }}</td>
                                <td class="px-3 py-4 text-slate-500">{{ $movement->reference_type ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-10 text-center text-slate-500">No stock movements recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
