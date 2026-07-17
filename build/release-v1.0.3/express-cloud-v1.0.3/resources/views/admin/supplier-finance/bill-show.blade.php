<x-layout.app title="{{ $bill->bill_number }} | Express Cloud">
    <x-layout.app-shell
        :page-title="$bill->bill_number"
        :page-description="$bill->supplier?->company_name.' · '.ucfirst($bill->status->value)"
    >
        <section class="grid gap-4 sm:grid-cols-[repeat(2,minmax(0,1fr))] xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total', 'value' => '₦'.number_format($bill->total_kobo / 100, 2)],
                ['label' => 'Paid', 'value' => '₦'.number_format($bill->paid_kobo / 100, 2)],
                ['label' => 'Outstanding', 'value' => '₦'.number_format($bill->balanceDueKobo() / 100, 2)],
                ['label' => 'Due date', 'value' => $bill->due_date?->format('d M Y') ?? 'Not set'],
            ] as $metric)
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-xl font-bold text-slate-950">{{ $metric['value'] }}</p>
                </x-ui.card>
            @endforeach
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
            <x-ui.card title="Bill lines">
                <div class="ec-responsive-table overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Quantity</th>
                                <th class="px-3 py-3">Unit cost</th>
                                <th class="px-3 py-3">Tax</th>
                                <th class="px-3 py-3">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($bill->lines as $line)
                                <tr>
                                    <td class="px-3 py-4">{{ $line->description }}</td>
                                    <td class="px-3 py-4">{{ app(\App\Services\Inventory\Quantity::class)->format($line->quantity_milliunits) }}</td>
                                    <td class="px-3 py-4">₦{{ number_format($line->unit_cost_kobo / 100, 2) }}</td>
                                    <td class="px-3 py-4">₦{{ number_format($line->tax_kobo / 100, 2) }}</td>
                                    <td class="px-3 py-4 font-semibold">₦{{ number_format($line->line_total_kobo / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                @if ($bill->balanceDueKobo() > 0)
                    <x-ui.card title="Record supplier payment">
                        <form method="POST" action="{{ route('admin.supplier-finance.bills.payments.store', $bill) }}" class="space-y-4">
                            @csrf
                            <select name="payment_method_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Select payment method</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <x-ui.input name="amount" type="number" step="0.01" label="Amount (₦)" required />
                            <x-ui.input name="reference" label="Payment reference" />
                            <x-ui.button type="submit" class="w-full">
                                Record payment
                            </x-ui.button>
                        </form>
                    </x-ui.card>
                @endif

                <x-ui.card title="Documents">
                    <div class="space-y-3">
                        @forelse ($bill->documents as $document)
                            <a
                                href="{{ route('admin.supplier-finance.bills.documents.download', [$bill, $document]) }}"
                                class="block rounded-lg border border-slate-200 p-3 text-sm font-medium text-blue-700 hover:bg-slate-50"
                            >
                                {{ $document->original_filename }}
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No documents attached.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
