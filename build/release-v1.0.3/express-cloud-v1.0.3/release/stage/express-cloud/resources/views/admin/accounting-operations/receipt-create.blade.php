<x-layout.app title="Receive payment | Express Cloud">
<x-layout.app-shell page-title="Receive payment" page-description="This creates a receipt only. It does not create an invoice, sale, or stock movement.">
<div data-page-header class="mb-5"></div>
<x-ui.card title="Payment receipt">
<form method="POST" action="{{ route('admin.accounting-operations.receipts.store') }}" class="space-y-5">
@csrf
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
<label><span class="mb-2 block text-sm font-medium">Branch</span><select name="branch_id" required><option value="">Select branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
<label><span class="mb-2 block text-sm font-medium">Customer (optional)</span><select name="customer_id"><option value="">No customer attached</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>@endforeach</select></label>
<label><span class="mb-2 block text-sm font-medium">Payment method</span><select name="payment_method_id" required><option value="">Select method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select></label>
<x-ui.input name="payer_name" label="Payer name" required />
<x-ui.input name="payer_phone" label="Payer phone" />
<x-ui.input name="amount" type="number" step="0.01" label="Amount received" required />
<x-ui.input name="reference" label="Payment reference" />
<x-ui.input name="purpose" label="Purpose" required />
<x-ui.input name="received_at" type="datetime-local" label="Received at" :value="now()->format('Y-m-d\TH:i')" required />
</div>
<label class="block"><span class="mb-2 block text-sm font-medium">Notes</span><textarea name="notes" rows="4" class="w-full rounded-lg border border-slate-300 p-3"></textarea></label>
<x-ui.button type="submit">Receive payment and issue receipt</x-ui.button>
</form>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
