                                <x-layout.app title="{{ $sale->sale_code }} | Express Cloud">
                                    <x-layout.app-shell
                                        :page-title="$sale->sale_code"
                                        :page-description="ucfirst($sale->sale_type->value).' · '.ucfirst($sale->status->value)"
                                    >
                                        {{-- Metrics cards – wrap in overflow-hidden --}}
                                        <div class="overflow-hidden">
                                            <section class="grid gap-4 sm:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-4">
                                                @foreach ([
                                                    ['label' => 'Grand total', 'value' => '₦'.number_format($sale->grand_total_kobo / 100, 2)],
                                                    ['label' => 'Paid', 'value' => '₦'.number_format($sale->paid_amount_kobo / 100, 2)],
                                                    ['label' => 'Balance due', 'value' => '₦'.number_format($sale->balanceDueKobo() / 100, 2)],
                                                    ['label' => 'Customer', 'value' => $sale->customer?->name ?? 'Walk-in'],
                                                ] as $metric)
                                                    <x-ui.card>
                                                        <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                                                        <p class="mt-3 text-xl font-bold text-slate-950">{{ $metric['value'] }}</p>
                                                    </x-ui.card>
                                                @endforeach
                                            </section>
                                        </div>

                                        {{-- Main content – with overflow protection --}}
                                        <div class="mt-6 min-w-0 overflow-hidden">
                                            <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                                                {{-- Left column: Line items --}}
                                                <div class="min-w-0 overflow-hidden">
                                                    <x-ui.card title="Line items">
                                                        <div class="ec-responsive-table overflow-x-auto">
                                                            <table class="w-full min-w-[760px] text-left text-sm">
                                                                <thead>
                                                                    <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                                                        <th class="px-3 py-3">Product</th>
                                                                        <th class="px-3 py-3">Quantity</th>
                                                                        <th class="px-3 py-3">Unit price</th>
                                                                        <th class="px-3 py-3">Discount</th>
                                                                        <th class="px-3 py-3">Tax</th>
                                                                        <th class="px-3 py-3">Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    @foreach ($sale->items as $item)
                                                                        <tr>
                                                                            <td class="px-3 py-4">{{ $item->product_name_snapshot }}</td>
                                                                            <td class="px-3 py-4">{{ app(\App\Services\Inventory\Quantity::class)->format($item->quantity_milliunits) }}</td>
                                                                            <td class="px-3 py-4">₦{{ number_format($item->unit_price_kobo / 100, 2) }}</td>
                                                                            <td class="px-3 py-4">₦{{ number_format($item->discount_amount_kobo / 100, 2) }}</td>
                                                                            <td class="px-3 py-4">₦{{ number_format($item->tax_amount_kobo / 100, 2) }}</td>
                                                                            <td class="px-3 py-4 font-semibold">₦{{ number_format($item->line_total_kobo / 100, 2) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </x-ui.card>
                                                </div>

                                                {{-- Right column: Payments and Record payment – with fixed max-width --}}
                                                <div class="min-w-0 max-w-full overflow-hidden">
                                                    <div class="space-y-6">
                                                        @if ($sale->sale_type->value !== 'quote' && $sale->balanceDueKobo() > 0)
                                                            <x-ui.card title="Record payment">
                                                                <form method="POST" action="{{ route('admin.sales.payments.store', $sale) }}" class="space-y-4">
                                                                    @csrf
                                                                    <select name="payment_method_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                                                        <option value="">Select method</option>
                                                                        @foreach ($paymentMethods as $method)
                                                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <x-ui.input name="amount" type="number" step="0.01" label="Amount (₦)" required />
                                                                    <x-ui.input name="reference" label="Reference" />
                                                                    <x-ui.button type="submit" class="w-full">Record payment</x-ui.button>
                                                                </form>
                                                            </x-ui.card>
                                                        @endif

                                                        <x-ui.card title="Payments">
                                                            <div class="space-y-3">
                                                                @forelse ($sale->payments as $payment)
                                                                    <div class="flex items-center justify-between text-sm">
                                                                        <span class="text-slate-600">{{ $payment->paymentMethod?->name }}</span>
                                                                        <span class="font-semibold text-slate-950">₦{{ number_format($payment->amount_kobo / 100, 2) }}</span>
                                                                    </div>
                                                                @empty
                                                                    <p class="text-sm text-slate-500">No payments recorded.</p>
                                                                @endforelse
                                                            </div>
                                                        </x-ui.card>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </x-layout.app-shell>
                                </x-layout.app>