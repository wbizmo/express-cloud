<x-layout.app title="Return {{ $sale->sale_code }} | Express Cloud">
<x-layout.app-shell :page-title="'Return '.$sale->sale_code" page-description="Return quantities cannot exceed the remaining quantity sold.">
<div data-page-header class="mb-5"></div>
<form method="POST" action="{{ route('staff.sales.returns.store',$sale) }}" class="space-y-6">
@csrf
<x-ui.card title="Items">
<div class="space-y-3">@foreach($sale->items as $index => $item)<div class="grid gap-4 rounded-xl border border-slate-200 p-4 md:grid-cols-[2fr_1fr_1fr]"><div><strong>{{ $item->product_name_snapshot }}</strong><p class="mt-1 text-sm text-slate-500">Sold {{ app(\App\Services\Inventory\Quantity::class)->format($item->quantity_milliunits) }}</p><input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}"></div><x-ui.input name="items[{{ $index }}][quantity]" type="number" step="0.001" label="Return quantity" value="0" /><input type="checkbox" name="items[{{ $index }}][restock]" value="1" checked data-label="Return to stock"></div>@endforeach</div>
</x-ui.card>
<x-ui.card title="Return details"><div class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))]"><label><span class="mb-2 block text-sm font-medium">Refund method</span><select name="refund_method"><option value="">No refund method recorded</option><option>Cash</option><option>Bank Transfer</option><option>Store Credit</option></select></label><x-ui.input name="reason" label="Reason" required /></div></x-ui.card>
<x-ui.button type="submit">Record return</x-ui.button>
</form>
</x-layout.app-shell>
</x-layout.app>
