<x-layout.app title="Record purchase return | Express Cloud">
<x-layout.app-shell page-title="Record purchase return" page-description="Select a recorded purchase and return quantities from its original product lines.">
<div data-page-header class="mb-5"></div>
<x-ui.card title="Purchase return">
<form method="POST" action="{{ route('admin.accounting-operations.purchase-returns.store') }}" class="space-y-5">
@csrf
<label><span class="mb-2 block text-sm font-medium">Purchase receipt</span><select name="purchase_receipt_id" id="purchase-receipt-select" required><option value="">Select purchase</option>@foreach($purchases as $purchase)<option value="{{ $purchase->id }}">{{ $purchase->receipt_number }} · {{ $purchase->purchased_at?->format('d M Y') }}</option>@endforeach</select></label>
<div class="space-y-4">@foreach($purchases as $purchase)@foreach($purchase->lines as $line)<div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[2fr_1fr]" data-purchase-line="{{ $purchase->id }}"><div><strong>{{ $line->product_id }}</strong><p class="text-sm text-slate-500">Received {{ number_format($line->quantity_milliunits/1000,3) }} · Unit cost ₦{{ number_format($line->unit_cost_kobo/100,2) }}</p><input type="hidden" name="lines[{{ $purchase->id }}-{{ $line->id }}][purchase_receipt_line_id]" value="{{ $line->id }}"></div><x-ui.input name="lines[{{ $purchase->id }}-{{ $line->id }}][quantity]" type="number" step="0.001" label="Return quantity" value="0" /></div>@endforeach@endforeach</div>
<x-ui.input name="supplier_credit_reference" label="Supplier credit note/reference" />
<label class="block"><span class="mb-2 block text-sm font-medium">Reason</span><textarea name="reason" rows="4" class="w-full rounded-lg border border-slate-300 p-3" required></textarea></label>
<x-ui.button type="submit">Record purchase return</x-ui.button>
</form>
</x-ui.card>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('purchase-receipt-select');
  const rows = [...document.querySelectorAll('[data-purchase-line]')];
  const update = () => rows.forEach(row => {
    row.hidden = row.dataset.purchaseLine !== select.value;
    row.querySelectorAll('input').forEach(input => input.disabled = row.hidden);
  });
  select.addEventListener('change', update);
  update();
});
</script>
</x-layout.app-shell>
</x-layout.app>
