<x-layout.app title="Supplier balances | Express Cloud">
    <x-layout.app-shell
        page-title="Supplier balances"
        page-description="Outstanding open and partially paid supplier bills."
    >
        <x-ui.card>
            <p class="text-sm font-medium text-slate-500">
                Total outstanding
            </p>
            <p class="mt-3 text-3xl font-bold text-slate-950">
                ₦{{ number_format($totalOutstandingKobo / 100, 2) }}
            </p>
        </x-ui.card>

        <x-ui.card title="Outstanding by supplier" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Supplier</th>
                            <th class="px-3 py-3">Code</th>
                            <th class="px-3 py-3">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td class="px-3 py-4 font-medium text-slate-950">
                                    {{ $supplier->company_name }}
                                </td>
                                <td class="px-3 py-4 font-mono text-xs text-slate-500">
                                    {{ $supplier->supplier_code }}
                                </td>
                                <td class="px-3 py-4 font-semibold text-slate-950">
                                    ₦{{ number_format(((int) $supplier->outstanding_kobo) / 100, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-10 text-center text-slate-500">
                                    No supplier balances.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
