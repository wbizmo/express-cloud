<x-layout.app title="{{ $customer->name }} receivable | Express Cloud">
<x-layout.app-shell :page-title="$customer->name" page-description="Open invoices and payments recorded against this customer.">
<div data-page-header class="mb-5"></div>
<x-ui.card title="Outstanding sales">
<div class="space-y-3">@forelse($sales as $sale)<article class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><strong>{{ $sale->sale_code }}</strong><p class="mt-1 text-sm text-slate-500">{{ $sale->sale_date?->format('d M Y') }} · {{ ucfirst($sale->status->value) }}</p></div><div class="text-right"><p class="font-semibold">Outstanding ₦{{ number_format($sale->balanceDueKobo()/100,2) }}</p><p class="text-sm text-slate-500">Total ₦{{ number_format($sale->grand_total_kobo/100,2) }}</p></div></div></article>@empty<p class="text-sm text-slate-500">This customer has no outstanding sales.</p>@endforelse</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
