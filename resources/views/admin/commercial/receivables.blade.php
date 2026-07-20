<x-layout.app title="Customer receivables | Express Cloud">
<x-layout.app-shell page-title="Customer receivables" page-description="Every customer balance comes from confirmed invoices and recorded payments.">
<div data-page-header class="mb-5"></div>
<form method="GET" class="mb-5 flex gap-3">
<x-ui.input name="search" label="Search customers" :value="request('search')" />
<div class="flex items-end"><x-ui.button type="submit">Search</x-ui.button></div>
</form>
<x-ui.card title="Outstanding customers">
<div class="ec-responsive-table overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead><tr>
<th class="px-3 py-3"><a href="{{ request()->fullUrlWithQuery(['sort' => 'name']) }}">Customer</a></th>
<th class="px-3 py-3">Phone</th>
<th class="px-3 py-3"><a href="{{ request()->fullUrlWithQuery(['sort' => 'outstanding']) }}">Outstanding</a></th>
<th class="px-3 py-3"></th>
</tr></thead><tbody>@forelse($customers as $customer)<tr class="border-t border-slate-100"><td class="px-3 py-4 font-semibold">{{ $customer->name }}</td><td class="px-3 py-4">{{ $customer->phone }}</td><td class="px-3 py-4 font-semibold {{ (int)$customer->outstanding_kobo > 0 ? 'text-red-700' : 'text-emerald-700' }}">₦{{ number_format(((int)$customer->outstanding_kobo)/100,2) }}</td><td class="px-3 py-4 text-right"><a class="font-semibold text-slate-900" href="{{ route('admin.commercial.receivables.show',$customer) }}">Open account</a></td></tr>@empty<tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No customers found.</td></tr>@endforelse</tbody></table></div>
<div class="mt-4">{{ $customers->links() }}</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
