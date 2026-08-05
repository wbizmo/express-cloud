<x-layout.app title="POS Workstation | Express Cloud">
    <x-layout.app-shell page-title="POS workstation" page-description="Barcode-ready checkout, held carts, split tenders, cashier shifts, cash control and confirmed receipt printing.">
        @if (! $openShift)
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($terminals as $terminal)
                    <x-ui.card title="{{ $terminal->name }}">
                        <p class="mb-4 text-sm text-slate-500">{{ $terminal->code }} · {{ ucfirst($terminal->printer_profile) }}</p>
                        <form method="POST" action="{{ route('admin.pos.open', $terminal) }}" class="space-y-3">@csrf
                            <x-ui.input name="opening_float_kobo" type="number" min="0" label="Opening float (kobo)" value="0" required />
                            <x-ui.button type="submit" class="w-full">Open cashier shift</x-ui.button>
                        </form>
                    </x-ui.card>
                @empty
                    <x-ui.card title="No active terminals"><p class="text-sm text-slate-500">Create and activate a POS terminal before opening a shift.</p></x-ui.card>
                @endforelse
            </div>
        @else
            <div class="mb-5 grid gap-4 md:grid-cols-4">
                <x-ui.card title="Shift"><div class="text-lg font-bold">{{ $openShift->shift_number }}</div><div class="text-sm text-emerald-600">Open since {{ $openShift->opened_at?->format('H:i') }}</div></x-ui.card>
                <x-ui.card title="Opening float"><div class="text-lg font-bold">₦{{ number_format($openShift->opening_float_kobo / 100, 2) }}</div></x-ui.card>
                <x-ui.card title="Held carts"><div class="text-lg font-bold">{{ $heldSales->count() }}</div></x-ui.card>
                <x-ui.card title="Terminal"><div class="text-sm font-semibold">{{ $openShift->pos_terminal_id }}</div></x-ui.card>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_420px]">
                <x-ui.card title="Fast checkout">
                    <form method="POST" action="{{ route('admin.pos.complete', $openShift) }}" class="space-y-4" id="pos-sale-form">@csrf
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                        <input type="hidden" name="sale_type" value="pos">
                        <input type="hidden" name="branch_id" value="{{ $openShift->branch_id }}">
                        <input type="hidden" name="pos_shift_id" value="{{ $openShift->id }}">
                        <input type="hidden" name="pos_terminal_id" value="{{ $openShift->pos_terminal_id }}">
                        <div class="grid gap-3 md:grid-cols-2">
                            <select name="customer_id" class="min-h-11 rounded-lg border px-3"><option value="">Walk-in customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select>
                            <input id="pos-search" placeholder="Scan barcode or filter product list" class="min-h-11 rounded-lg border px-3" autofocus>
                        </div>
                        <div class="grid gap-3 md:grid-cols-3">
                            <select name="items[0][product_id]" required class="min-h-11 rounded-lg border px-3" id="pos-product"><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-search="{{ strtolower($product->name.' '.$product->sku.' '.$product->barcode) }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select>
                            <x-ui.input name="items[0][quantity]" label="Quantity" value="1" required />
                            <x-ui.input name="items[0][unit_price]" label="Unit price (naira)" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-3">
                            <select name="payments[0][payment_method_id]" required class="min-h-11 rounded-lg border px-3"><option value="">Payment method</option>@foreach($methods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select>
                            <x-ui.input name="payments[0][amount]" label="Amount paid (naira)" required />
                            <x-ui.input name="payments[0][reference]" label="Reference" />
                        </div>
                        <x-ui.input name="notes" label="Sale note" />
                        <div class="flex flex-wrap gap-3"><x-ui.button type="submit">Complete confirmed sale</x-ui.button></div>
                    </form>
                    @if ($products->hasPages())<div class="mt-4 border-t pt-4">{{ $products->links() }}</div>@endif
                </x-ui.card>

                <div class="space-y-6">
                    <x-ui.card title="Cash movement"><form method="POST" action="{{ route('admin.pos.cash', $openShift) }}" class="space-y-3">@csrf<select name="movement_type" class="min-h-11 w-full rounded-lg border px-3"><option value="pay_in">Pay in</option><option value="pay_out">Pay out</option><option value="cash_refund">Cash refund</option></select><x-ui.input name="amount_kobo" type="number" min="1" label="Amount (kobo)" required /><x-ui.input name="memo" label="Required memo" required /><x-ui.button type="submit" class="w-full">Record cash movement</x-ui.button></form></x-ui.card>

                    <x-ui.card title="Held carts">
                        <div class="space-y-3">@forelse($heldSales as $held)<div class="rounded-lg border p-3"><div class="font-semibold">{{ $held->hold_token }}</div><div class="text-xs text-slate-500">₦{{ number_format($held->estimated_total_kobo/100,2) }}</div><form method="POST" action="{{ route('admin.pos.resume', [$openShift, $held]) }}" class="mt-2">@csrf<button class="text-sm font-semibold text-blue-700">Resume</button></form></div>@empty<p class="text-sm text-slate-500">No held carts.</p>@endforelse</div>
                    </x-ui.card>

                    <x-ui.card title="Close and reconcile shift"><form method="POST" action="{{ route('admin.pos.close', $openShift) }}" class="space-y-3">@csrf @foreach($methods as $method)<x-ui.input name="tenders[{{ $method->id }}]" type="number" min="0" label="{{ $method->name }} counted (kobo)" value="0" />@endforeach<x-ui.input name="note" label="Closing note" required /><x-ui.button type="submit" class="w-full">Close shift</x-ui.button></form></x-ui.card>
                </div>
            </div>
        @endif
    </x-layout.app-shell>
    <script>
        const search = document.getElementById('pos-search');
        const select = document.getElementById('pos-product');
        if (search && select) search.addEventListener('input', () => {
            const value = search.value.toLowerCase();
            [...select.options].forEach((option, index) => {
                if (index === 0) return;
                option.hidden = value !== '' && !option.dataset.search.includes(value);
            });
        });
    </script>
</x-layout.app>
