<x-layout.app>
    <x-layout.app-shell page-title="My workspace" page-description="Your permission-scoped operational dashboard.">
        <div class="grid gap-4 sm:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-4">
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales today</p><p class="mt-2 text-2xl font-bold">{{ number_format($todaySalesCount) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue today</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($todayRevenueKobo / 100, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue this month</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($monthRevenueKobo / 100, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding on my sales</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($outstandingKobo / 100, 2) }}</p></x-ui.card>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.5fr_1fr]">
            <x-ui.card>
                <div class="flex items-center justify-between"><div><h2 class="font-semibold">Recent activity</h2><p class="text-sm text-slate-500">Invoices, POS sales and quotes created by you.</p></div></div>
                <div class="ec-responsive-table mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3">Reference</th><th>Type</th><th>Status</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Due</th></tr></thead>
                        <tbody class="divide-y">
                            @forelse($recentSales as $sale)
                                <tr><td class="py-3 font-medium">{{ $sale->sale_code }}</td><td>{{ ucfirst($sale->sale_type) }}</td><td>{{ ucfirst($sale->status) }}</td><td>{{ $sale->sale_date }}</td><td class="text-right">₦{{ number_format(((int) $sale->grand_total_kobo) / 100, 2) }}</td><td class="text-right">₦{{ number_format(((int) $sale->balance_due_kobo) / 100, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-slate-500">No sales activity yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="space-y-5">
                <x-ui.card>
                    <h2 class="font-semibold">Quick actions</h2>
                    <div class="mt-4 grid gap-2">
                        @if($permissions->contains('sales.create'))<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.sales.create') }}">Create sale or invoice</a>@endif
                        @if($permissions->intersect(['sales.view','sales.view.own','sales.view.all'])->isNotEmpty())<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.sales.index') }}">View sales</a>@endif
                        @if($permissions->contains('customers.view'))<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.customers.index') }}">Customers</a>@endif
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
