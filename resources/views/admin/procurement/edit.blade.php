<x-layout.app title="Edit {{ $order->order_number }} | Express Cloud">
    <x-layout.app-shell
        :page-title="'Edit '.$order->order_number"
        page-description="Revising an approved, unreceived order resets it to draft and requires approval again."
    >
        @php
            $initialLines = $order->lines->values()->map(fn ($line) => [
                'product_id' => (string) $line->product_id,
                'quantity' => number_format($line->ordered_quantity_milliunits / 1000, 3, '.', ''),
                'unit_cost' => number_format($line->unit_cost_kobo / 100, 2, '.', ''),
                'tax_rate_percent' => number_format($line->tax_rate_basis_points / 100, 2, '.', ''),
            ]);
        @endphp

        <div class="mx-auto max-w-5xl">
            <x-ui.card title="Order details" description="Received history cannot be rewritten. This form is available only before the first receipt.">
                <form
                    method="POST"
                    action="{{ route('admin.procurement.orders.update', $order) }}"
                    class="space-y-6"
                    x-data="{ lines: @js($initialLines), nextId: {{ max(1, $initialLines->count()) }} }"
                >
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.searchable-select name="supplier_id" :options="$suppliers->map(fn ($s) => ['value' => $s->id, 'label' => $s->company_name.' — '.$s->supplier_code])" :selected="(string) $order->supplier_id" placeholder="Select supplier" required />
                        <x-ui.searchable-select name="branch_id" :options="$branches->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])" :selected="(string) $order->branch_id" placeholder="Receiving branch" required />
                        <x-ui.input name="expected_at" type="date" label="Expected date" :value="$order->expected_at?->format('Y-m-d')" />
                        <div class="ec-readonly-field">
                            <span>Status</span>
                            <strong>{{ str_replace('_', ' ', ucfirst($order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status)) }}</strong>
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-slate-950">Order lines</h2>
                                <p class="mt-1 text-sm text-slate-500">Change quantities, prices, tax or products before approval.</p>
                            </div>
                            <button type="button" class="ec-action-link" x-on:click="lines.push({ product_id: '', quantity: '', unit_cost: '', tax_rate_percent: '' })">
                                <x-ui.icon name="add" :size="17" />
                                <span>Add line</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(line, index) in lines" :key="index">
                                <div class="ec-line-editor">
                                    <select :name="`lines[${index}][product_id]`" x-model="line.product_id" required>
                                        <option value="">Select product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} — {{ $product->sku }}</option>
                                        @endforeach
                                    </select>
                                    <input :name="`lines[${index}][quantity]`" x-model="line.quantity" required placeholder="Quantity">
                                    <input :name="`lines[${index}][unit_cost]`" x-model="line.unit_cost" type="number" step="0.01" required placeholder="Unit cost (₦)">
                                    <input :name="`lines[${index}][tax_rate_percent]`" x-model="line.tax_rate_percent" type="number" step="0.01" placeholder="Tax rate %">
                                    <button type="button" class="ec-icon-button danger" x-on:click="if (lines.length > 1) lines.splice(index, 1)" aria-label="Remove line">
                                        <x-ui.icon name="delete" :size="18" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                        <textarea name="reference_note" required class="ec-textarea">{{ old('reference_note', $order->reference_note) }}</textarea>
                    </label>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
                        <a href="{{ route('admin.procurement.orders.index') }}" class="ec-secondary-link">
                            <x-ui.icon name="close" :size="17" />
                            <span>Discard changes</span>
                        </a>
                        <x-ui.button type="submit" icon="save">Save and return to draft</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
