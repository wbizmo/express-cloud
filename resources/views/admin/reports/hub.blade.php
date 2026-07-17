<x-layout.app title="Reports | Express Cloud">
<x-layout.app-shell page-title="Reports" page-description="Sales, top items, low stock, and export actions in one filterable hub.">
<form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
<x-ui.input name="from" type="date" label="From" :value="$from" />
<x-ui.input name="to" type="date" label="To" :value="$to" />
<label class="block"><span class="mb-2 block text-sm font-medium text-slate-700">Branch</span><select name="branch" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($selectedBranch === $branch->id)>{{ $branch->name }}</option>@endforeach</select></label>
<div class="flex items-end"><x-ui.button type="submit" class="w-full">Apply filters</x-ui.button></div>
</form>
<div class="mb-6 flex flex-wrap gap-3">
<a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold" href="{{ route('admin.reports.exports.sales', request()->query()) }}">Export sales CSV</a>
<a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold" href="{{ route('admin.reports.exports.staff', request()->query()) }}">Export staff CSV</a>
<a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold" href="{{ route('admin.reports.exports.low-stock') }}">Export low stock CSV</a>
</div>
<x-ui.card title="Recent sales">
<div class="ec-responsive-table overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-3">Code</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Branch</th><th class="px-3 py-3">Staff</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($sales as $row)<tr><td class="px-3 py-4 font-mono">{{ $row->sale_code }}</td><td class="px-3 py-4">{{ ucfirst($row->sale_type) }}</td><td class="px-3 py-4">{{ $row->sale_date }}</td><td class="px-3 py-4">{{ $row->branch_name }}</td><td class="px-3 py-4">{{ trim($row->first_name.' '.$row->last_name) }}</td><td class="px-3 py-4 font-semibold">₦{{ number_format(((int)$row->grand_total_kobo)/100,2) }}</td><td class="px-3 py-4">{{ ucfirst($row->status) }}</td></tr>@empty<tr><td colspan="7" class="px-3 py-10 text-center text-slate-500">No sales in this period.</td></tr>@endforelse</tbody></table></div>
</x-ui.card>
<div class="mt-6 grid gap-6 xl:grid-cols-[repeat(2,minmax(0,1fr))]">
<x-ui.card title="Top items"><div class="space-y-3">@forelse($topItems as $row)<div class="flex justify-between rounded-lg border border-slate-200 p-3"><span>{{ $row->product_name_snapshot }}</span><strong>{{ app(\App\Services\Inventory\Quantity::class)->format((int)$row->units_milliunits) }} units</strong></div>@empty<p class="text-sm text-slate-500">No item data.</p>@endforelse</div></x-ui.card>
<x-ui.card title="Low stock"><div class="space-y-3">@forelse($lowStock as $row)<div class="rounded-lg border border-amber-200 bg-amber-50 p-3"><strong>{{ $row->product_name }}</strong><p class="mt-1 text-sm text-slate-600">{{ $row->branch_name }} · {{ app(\App\Services\Inventory\Quantity::class)->format((int)$row->quantity_milliunits) }} remaining</p></div>@empty<p class="text-sm text-slate-500">No open alerts.</p>@endforelse</div></x-ui.card>
</div>
</x-layout.app-shell>
</x-layout.app>
