<x-layout.app title="Staff performance | Express Cloud">
    <x-layout.app-shell
        page-title="Staff performance"
        page-description="Sales, revenue, units, and customers served, ranked by performance."
    >
        <form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <x-ui.input name="from" type="date" label="From" :value="$from" />
            <x-ui.input name="to" type="date" label="To" :value="$to" />
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Branch</span>
                <select name="branch" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($selectedBranch === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end"><x-ui.button type="submit" class="w-full">Apply filters</x-ui.button></div>
        </form>

        <x-ui.card title="Performance ranking">
            <div class="ec-responsive-table overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-3">Rank</th><th class="px-3 py-3">Staff</th><th class="px-3 py-3">Sales</th><th class="px-3 py-3">Revenue</th><th class="px-3 py-3">Units</th><th class="px-3 py-3">Customers</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $index => $row)
                            <tr><td class="px-3 py-4 font-semibold">{{ $index + 1 }}</td><td class="px-3 py-4">{{ trim($row->first_name.' '.$row->last_name) }}</td><td class="px-3 py-4">{{ $row->sales_count }}</td><td class="px-3 py-4 font-semibold">₦{{ number_format(((int) $row->revenue_kobo) / 100, 2) }}</td><td class="px-3 py-4">{{ app(\App\Services\Inventory\Quantity::class)->format((int) $row->units_milliunits) }}</td><td class="px-3 py-4">{{ $row->customers_served }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No staff performance data in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
