<x-layout.app title="Purchases | Express Cloud">
<x-layout.app-shell page-title="Recorded purchases" page-description="Supplier purchases and stock intake share one traceable record.">
<div data-page-header class="mb-5 flex justify-end"><a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.commercial.purchases.create') }}">Record purchase</a></div>
<x-ui.card title="Purchase history">
<div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead><tr><th class="px-3 py-3">Receipt</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Supplier</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Status</th></tr></thead><tbody>@forelse($receipts as $receipt)<tr class="border-t border-slate-100"><td class="px-3 py-4 font-mono">{{ $receipt->receipt_number }}</td><td class="px-3 py-4">{{ $receipt->purchased_at?->format('d M Y') }}</td><td class="px-3 py-4">{{ $receipt->supplier_id }}</td><td class="px-3 py-4 font-semibold">₦{{ number_format($receipt->total_kobo/100,2) }}</td><td class="px-3 py-4">{{ ucfirst($receipt->status->value) }}</td></tr>@empty<tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No purchases recorded.</td></tr>@endforelse</tbody></table></div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
