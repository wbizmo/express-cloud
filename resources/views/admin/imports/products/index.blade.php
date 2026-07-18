<x-layout.app title="Product imports | Express Cloud">
    <x-layout.app-shell
        page-title="Product imports"
        page-description="Validate Excel workbooks before any product record is created or updated."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.imports.products.template') }}"
                class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                <x-ui.icon name="download" :size="17" />
                Download Sample Excel File
            </a>
        </x-slot:actions>

        <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
            <x-ui.card
                title="Upload product workbook"
                description="Excel .xlsx only. The workbook is validated before import."
            >
                <form
                    method="POST"
                    action="{{ route('admin.imports.products.store') }}"
                    enctype="multipart/form-data"
                    class="space-y-5"
                >
                    @csrf

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">
                            Excel workbook
                        </span>
                        <input
                            type="file"
                            name="workbook"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-600"
                        >
                    </label>

                    <div class="rounded-lg bg-slate-50 p-4 text-xs leading-5 text-slate-500">
                        Product quantity is not imported here. Opening stock is
                        recorded through inventory workflows so every quantity
                        change has a movement record.
                    </div>

                    <x-ui.button type="submit" class="w-full">
                        Validate workbook
                    </x-ui.button>
                </form>
            </x-ui.card>

            <div class="min-w-0 max-w-full overflow-hidden"><div class="min-w-0 max-w-full overflow-hidden"><x-ui.card title="Import history">
                <div class="ec-responsive-table overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">File</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Rows</th>
                                <th class="px-3 py-3">Products</th>
                                <th class="px-3 py-3">Uploaded by</th>
                                <th class="px-3 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($imports as $import)
                                <tr>
                                    <td class="px-3 py-4">
                                        <a
                                            href="{{ route('admin.imports.products.show', $import) }}"
                                            class="font-medium text-blue-700 hover:underline"
                                        >
                                            {{ $import->original_filename }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-4">
                                        <x-ui.status-badge
                                            :tone="match ($import->status->value) {
                                                'completed' => 'success',
                                                'failed', 'failed_validation' => 'danger',
                                                'processing' => 'warning',
                                                default => 'info',
                                            }"
                                        >
                                            {{ str_replace('_', ' ', ucfirst($import->status->value)) }}
                                        </x-ui.status-badge>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        {{ $import->valid_rows }} valid /
                                        {{ $import->invalid_rows }} invalid
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        {{ $import->created_products }} created,
                                        {{ $import->updated_products }} updated
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        {{ $import->account?->displayName() ?? 'Unavailable' }}
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        {{ $import->created_at?->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-slate-500">
                                        No product imports have been uploaded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card></div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
