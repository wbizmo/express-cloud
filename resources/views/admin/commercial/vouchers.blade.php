<x-layout.app title="Discount vouchers | Express Cloud">
<x-layout.app-shell page-title="Discount vouchers" page-description="Reusable checkout codes with date, value, cap, and usage controls.">
<div data-page-header class="mb-5"></div>
<div class="grid gap-6 xl:grid-cols-[420px_1fr]">
<x-ui.card title="Create voucher">
<form method="POST" action="{{ route('admin.commercial.vouchers.store') }}" class="space-y-4">
@csrf
<x-ui.input name="code" label="Voucher code" required />
<x-ui.input name="name" label="Internal name" required />
<div class="grid gap-4 sm:grid-cols-2">
<label class="block"><span class="mb-2 block text-sm font-medium">Discount type</span><select name="value_type"><option value="fixed">Fixed amount</option><option value="percentage">Percentage</option></select></label>
<x-ui.input name="value" type="number" step="0.01" label="Value" required />
</div>
<div class="grid gap-4 sm:grid-cols-2">
<x-ui.input name="minimum_sale" type="number" step="0.01" label="Minimum sale" />
<x-ui.input name="maximum_discount" type="number" step="0.01" label="Maximum discount" />
</div>
<div class="grid gap-4 sm:grid-cols-3">
<x-ui.input name="usage_limit" type="number" label="Usage limit" />
<x-ui.input name="starts_at" type="datetime-local" label="Starts" />
<x-ui.input name="ends_at" type="datetime-local" label="Ends" />
</div>
<input type="checkbox" name="is_active" value="1" checked data-label="Voucher active">
<x-ui.button type="submit">Create voucher</x-ui.button>
</form>
</x-ui.card>
<x-ui.card title="Existing vouchers">
<div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead><tr><th class="px-3 py-3">Code</th><th class="px-3 py-3">Name</th><th class="px-3 py-3">Value</th><th class="px-3 py-3">Usage</th><th class="px-3 py-3">Status</th></tr></thead><tbody>@forelse($vouchers as $voucher)<tr class="border-t border-slate-100"><td class="px-3 py-4 font-mono font-semibold">{{ $voucher->code }}</td><td class="px-3 py-4">{{ $voucher->name }}</td><td class="px-3 py-4">{{ $voucher->value_type->value === 'fixed' ? '₦'.number_format($voucher->value/100,2) : number_format($voucher->value/100,2).'%' }}</td><td class="px-3 py-4">{{ $voucher->usage_count }} / {{ $voucher->usage_limit ?? '∞' }}</td><td class="px-3 py-4">{{ ucfirst($voucher->status->value) }}</td></tr>@empty<tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No vouchers created.</td></tr>@endforelse</tbody></table></div>
</x-ui.card>
</div>
</x-layout.app-shell>
</x-layout.app>
