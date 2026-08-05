<x-layout.app title="Sales Workflows | Express Cloud">
    <x-layout.app-shell page-title="Sales, orders and quotations" page-description="One canonical commercial engine controls quotations, orders, invoices, conversion, fulfilment and document history.">
        <x-slot:actions>
            <a href="{{ route('admin.sales.workflows.export', request()->query()) }}" class="inline-flex min-h-11 items-center rounded-lg border px-4 text-sm font-semibold">Stream CSV</a>
            <a href="{{ route('admin.sales.create') }}" class="inline-flex min-h-11 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white">New document</a>
        </x-slot:actions>

        <form method="GET" class="mb-5 flex flex-wrap gap-3 rounded-xl border bg-white p-4">
            <select name="type" class="min-h-11 rounded-lg border px-3">
                <option value="">All document types</option>
                @foreach (['quote' => 'Quotation', 'order' => 'Sales order', 'invoice' => 'Invoice'] as $value => $label)
                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit">Filter</x-ui.button>
        </form>

        <x-ui.card title="Commercial document register">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm">
                    <thead><tr class="border-b text-left text-xs uppercase text-slate-500"><th class="p-3">Document</th><th class="p-3">Type</th><th class="p-3">Customer</th><th class="p-3">State</th><th class="p-3">Fulfilment</th><th class="p-3 text-right">Total</th><th class="p-3">Actions</th></tr></thead>
                    <tbody>
                    @forelse ($documents as $document)
                        <tr class="border-b align-top">
                            <td class="p-3"><a class="font-semibold text-blue-700" href="{{ route('admin.sales.show', $document) }}">{{ $document->sale_code }}</a><div class="text-xs text-slate-500">v{{ $document->document_version }} · {{ $document->sale_date?->format('d M Y') }}</div></td>
                            <td class="p-3">{{ ucfirst($document->sale_type->value) }}</td>
                            <td class="p-3">{{ $document->customer?->name ?? 'Walk-in customer' }}</td>
                            <td class="p-3">{{ ucfirst($document->workflow_state) }}</td>
                            <td class="p-3">{{ ucfirst(str_replace('_', ' ', $document->fulfilment_status)) }}</td>
                            <td class="p-3 text-right font-semibold">₦{{ number_format($document->grand_total_kobo / 100, 2) }}</td>
                            <td class="p-3">
                                @if (in_array($document->sale_type->value, ['quote', 'order'], true))
                                    <form method="POST" action="{{ route('admin.sales.workflows.convert', $document) }}" class="grid gap-2">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                                        <select name="target_type" class="min-h-9 rounded border px-2 text-xs">
                                            @if ($document->sale_type->value === 'quote')<option value="order">Convert to order</option>@endif
                                            <option value="invoice">Convert to invoice</option>
                                        </select>
                                        <input name="memo" required placeholder="Conversion memo" class="min-h-9 rounded border px-2 text-xs">
                                        <button class="min-h-9 rounded bg-slate-900 px-3 text-xs font-semibold text-white">Convert</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">Confirmed financial document</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-10 text-center text-slate-500">No sales documents found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($documents->hasPages())<div class="border-t p-4">{{ $documents->links() }}</div>@endif
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
