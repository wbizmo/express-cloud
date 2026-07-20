                    <x-layout.app title="Sales | Express Cloud">
                        <x-layout.app-shell
                            page-title="Sales"
                            page-description="Invoices, quotes, and POS transactions share one consistent sales engine."
                        >
                            <x-slot:actions>
                                @can('sales.export')
                                    <a href="{{ route('admin.sales.export', ['format' => 'csv'] + request()->query()) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</a>
                                    <a href="{{ route('admin.sales.export', ['format' => 'xlsx'] + request()->query()) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</a>
                                    <a href="{{ route('admin.sales.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</a>
                                @endcan
                                <a href="{{ route('admin.sales.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                                    <x-ui.icon name="plus" :size="17" />
                                    New sale
                                </a>
                            </x-slot:actions>

                            <x-ui.card title="Sales history">
                                <div class="ec-responsive-table overflow-x-auto">
                                    <table class="w-full min-w-[1000px] text-left text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                                <th class="px-3 py-3">Code</th>
                                                <th class="px-3 py-3">Type</th>
                                                <th class="px-3 py-3">Customer</th>
                                                <th class="px-3 py-3">Branch</th>
                                                <th class="px-3 py-3">Staff</th>
                                                <th class="px-3 py-3">Total</th>
                                                <th class="px-3 py-3">Paid</th>
                                                <th class="px-3 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($sales as $sale)
                                                <tr>
                                                    <td class="px-3 py-4">
                                                        <a href="{{ route('admin.sales.show', $sale) }}" class="font-semibold text-blue-700 hover:underline">
                                                            {{ $sale->sale_code }}
                                                        </a>
                                                    </td>
                                                    <td class="px-3 py-4">{{ ucfirst($sale->sale_type->value) }}</td>
                                                    <td class="px-3 py-4">{{ $sale->customer?->name ?? 'Walk-in customer' }}</td>
                                                    <td class="px-3 py-4">{{ $sale->branch?->name }}</td>
                                                    <td class="px-3 py-4">{{ $sale->soldBy?->displayName() }}</td>
                                                    <td class="px-3 py-4 font-semibold">₦{{ number_format($sale->grand_total_kobo / 100, 2) }}</td>
                                                    <td class="px-3 py-4">₦{{ number_format($sale->paid_amount_kobo / 100, 2) }}</td>
                                                    <td class="px-3 py-4">
                                                        <x-ui.status-badge :tone="$sale->status->value === 'paid' ? 'success' : 'info'">
                                                            {{ ucfirst($sale->status->value) }}
                                                        </x-ui.status-badge>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="px-3 py-10 text-center text-slate-500">No sales recorded.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if ($sales->hasPages())
                                    <div class="border-t border-slate-200 px-3 py-4">
                                        {{ $sales->links() }}
                                    </div>
                                @endif
                            </x-ui.card>
                        </x-layout.app-shell>
                    </x-layout.app>