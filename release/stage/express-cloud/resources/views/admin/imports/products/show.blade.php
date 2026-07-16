<x-layout.app title="Import preview | Express Cloud">
    <x-layout.app-shell
        page-title="Import preview"
        :page-description="$import->original_filename"
    >
        <x-slot:actions>
            @if ($import->error_report_path)
                <a
                    href="{{ route('admin.imports.products.errors', $import) }}"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50"
                >
                    <x-ui.icon name="file-warning" :size="17" />
                    Download Error Excel File
                </a>
            @endif

            @if ($import->status->value === 'validated')
                <form
                    method="POST"
                    action="{{ route('admin.imports.products.process', $import) }}"
                >
                    @csrf
                    <x-ui.button type="submit">
                        Confirm and import
                    </x-ui.button>
                </form>
            @endif
        </x-slot:actions>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total rows', 'value' => $import->total_rows],
                ['label' => 'Valid rows', 'value' => $import->valid_rows],
                ['label' => 'Invalid rows', 'value' => $import->invalid_rows],
                ['label' => 'Status', 'value' => str_replace('_', ' ', ucfirst($import->status->value))],
            ] as $metric)
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-2xl font-bold text-slate-950">{{ $metric['value'] }}</p>
                </x-ui.card>
            @endforeach
        </section>

        <x-ui.card
            title="Workbook preview"
            description="Up to the first 50 rows are shown. No product records change until the import is confirmed."
            class="mt-6"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Row</th>
                            <th class="px-3 py-3">SKU</th>
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Category</th>
                            <th class="px-3 py-3">Brand</th>
                            <th class="px-3 py-3">Price</th>
                            <th class="px-3 py-3">Inventory</th>
                            <th class="px-3 py-3">Validation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($previewRows as $row)
                            @php
                                $payload = is_array($row->payload) ? $row->payload : [];
                                $errors = is_array($row->errors) ? $row->errors : [];
                            @endphp
                            <tr>
                                <td class="px-3 py-4">{{ $row->row_number }}</td>
                                <td class="px-3 py-4 font-mono">{{ $payload['sku'] ?? '—' }}</td>
                                <td class="px-3 py-4">{{ $payload['name'] ?? '—' }}</td>
                                <td class="px-3 py-4">{{ $payload['category'] ?? '—' }}</td>
                                <td class="px-3 py-4">{{ $payload['brand'] ?? '—' }}</td>
                                <td class="px-3 py-4">₦{{ $payload['default_price'] ?? '0' }}</td>
                                <td class="px-3 py-4">{{ $payload['track_inventory'] ?? '—' }}</td>
                                <td class="px-3 py-4">
                                    @if ($row->is_valid)
                                        <x-ui.status-badge tone="success">Valid</x-ui.status-badge>
                                    @else
                                        <div class="space-y-1 text-red-700">
                                            @foreach ($errors as $error)
                                                <p>{{ $error }}</p>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
