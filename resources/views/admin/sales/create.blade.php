<x-layout.app title="New sale | Express Cloud">
    <x-layout.app-shell page-title="New sale" page-description="Choose a branch, then scan or search products using barcode, SKU, or name.">
        <form method="POST" action="{{ route('admin.sales.store') }}" class="min-w-0 space-y-6" x-data="saleBuilder()">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <x-ui.card title="Sale details">
                <div class="grid min-w-0 gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-3">
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Sale type</span><select name="sale_type" required class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="invoice">Invoice</option><option value="quote">Quote</option><option value="pos">POS</option></select></label>
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Branch</span><select name="branch_id" required x-model="branchId" x-on:change="resetLines()" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">Select branch first</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Customer</span><input type="search" placeholder="Search customer or leave blank for walk-in" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" data-customer-search><input type="hidden" name="customer_id" data-customer-id></label>
                </div>
            </x-ui.card>

            <x-ui.card title="Products" description="USB and Bluetooth barcode scanners work like keyboard input. Select the branch before scanning.">
                <div x-show="!branchId" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Select the sale branch before scanning products.</div>
                <div x-show="branchId" class="min-w-0 space-y-4">
                    <x-catalog.product-scanner name="scanner_product_id" branch-field="branch_id" context="sale" />
                    <div class="ec-responsive-table" x-show="lines.length">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead><tr class="border-b text-left"><th class="p-3">Product</th><th class="p-3">Available</th><th class="p-3">Quantity</th><th class="p-3">Branch price</th><th class="p-3">Discount</th><th class="p-3"></th></tr></thead>
                            <tbody><template x-for="(line,index) in lines" :key="line.key"><tr class="border-b align-top"><td class="p-3"><input type="hidden" :name="`items[${index}][product_id]`" :value="line.id"><strong x-text="line.name"></strong><small class="block text-slate-500" x-text="line.sku"></small></td><td class="p-3" x-text="line.track_inventory ? line.quantity : 'Untracked'"></td><td class="p-3"><input :name="`items[${index}][quantity]`" x-model="line.saleQuantity" required class="w-28 rounded-lg border p-2"></td><td class="p-3"><input :name="`items[${index}][unit_price]`" x-model="line.price" type="number" step="0.01" readonly class="w-32 rounded-lg border bg-slate-50 p-2"></td><td class="p-3"><input :name="`items[${index}][discount]`" value="0" type="number" step="0.01" class="w-28 rounded-lg border p-2"></td><td class="p-3"><button type="button" x-on:click="lines.splice(index,1)" class="font-semibold text-red-700">Remove</button></td></tr></template></tbody>
                        </table>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Payments" description="Quotes ignore payments until converted. Invoices may be unpaid or partially paid."><div class="grid min-w-0 gap-3 md:grid-cols-3"><select name="payments[0][payment_method_id]" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">Payment method</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select><input name="payments[0][amount]" type="number" step="0.01" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Amount"><input name="payments[0][reference]" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Reference"></div></x-ui.card>
            <x-ui.card title="Notes"><textarea name="notes" class="min-h-28 w-full min-w-0 rounded-lg border border-slate-300 p-3 text-sm"></textarea></x-ui.card>
            <div class="flex justify-end"><x-ui.button type="submit">Complete sale</x-ui.button></div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
<script>
function saleBuilder(){return{branchId:'',lines:[],init(){this.$el.addEventListener('product-selected',e=>this.add(e.detail));},add(item){const existing=this.lines.find(l=>l.id===item.id);if(existing){existing.saleQuantity=String(Number(existing.saleQuantity)+1);return;}this.lines.push({...item,key:item.id+'-'+Date.now(),saleQuantity:'1'});},resetLines(){this.lines=[];}}}
</script>
