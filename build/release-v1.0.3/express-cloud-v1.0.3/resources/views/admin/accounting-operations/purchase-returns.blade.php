<x-layout.app title="Purchase returns | Express Cloud">
<x-layout.app-shell page-title="Purchase returns" page-description="Return previously received catalogue products to their supplier and reduce branch stock.">
<div data-page-header class="mb-5 flex justify-end"><a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.accounting-operations.purchase-returns.create') }}">Record purchase return</a></div>
<x-ui.card title="Return history">
<div class="ec-responsive-table overflow-x-auto"><table class="w-full min-w-[800px] text-left text-sm"><thead><tr><th class="px-3 py-3">Return</th><th class="px-3 py-3">Purchase</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Date</th><th class="px-3 py-3">Documents</th></tr></thead><tbody>@forelse($returns as $return)<tr class="border-t border-slate-100"><td class="px-3 py-4 font-mono">{{ $return->return_number }}</td><td class="px-3 py-4">{{ $return->purchase_receipt_id }}</td><td class="px-3 py-4 font-semibold">₦{{ number_format($return->total_kobo/100,2) }}</td><td class="px-3 py-4">{{ $return->returned_at?->format('d M Y H:i') }}</td><td class="px-3 py-4"><a href="{{ route('admin.accounting-operations.documents.pdf',['purchase_return',$return]) }}">PDF</a> · <a href="{{ route('admin.accounting-operations.documents.spreadsheet',['purchase_return',$return]) }}">Spreadsheet</a></td></tr>@empty<tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No purchase returns recorded.</td></tr>@endforelse</tbody></table></div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
