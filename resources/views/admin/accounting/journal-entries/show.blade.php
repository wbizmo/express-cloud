<x-layout.app title="Journal Entry {{ $entry->journal_number }} | Express Cloud">
    <x-layout.app-shell
        page-title="Journal Entry #{{ $entry->journal_number }}"
        page-description="Details of the journal entry."
    >
        @if (session('status'))
            <div class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <x-ui.card>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <span class="text-sm font-medium text-slate-500">Date</span>
                    <p class="text-sm">{{ $entry->entry_date->format('Y-m-d') }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-slate-500">Period</span>
                    <p class="text-sm">{{ $entry->accountingPeriod?->name ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-slate-500">Status</span>
                    <x-ui.status-badge :tone="match($entry->status) {
                        'draft' => 'neutral',
                        'posted' => 'success',
                        'reversed' => 'warning',
                        default => 'neutral'
                    }">
                        {{ ucfirst($entry->status) }}
                    </x-ui.status-badge>
                </div>
                <div>
                    <span class="text-sm font-medium text-slate-500">Memo</span>
                    <p class="text-sm">{{ $entry->memo }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-slate-500">Created by</span>
                    <p class="text-sm">{{ $entry->createdByAccount?->full_name ?? 'System' }}</p>
                </div>
                @if ($entry->posted_at)
                    <div>
                        <span class="text-sm font-medium text-slate-500">Posted at</span>
                        <p class="text-sm">{{ $entry->posted_at->format('Y-m-d H:i') }}</p>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-medium text-slate-700">Lines</h3>
                <div class="ec-responsive-table overflow-x-auto mt-2">
                    <table class="w-full min-w-[500px] text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2 text-left">Account</th>
                                <th class="px-2 py-2 text-left">Debit</th>
                                <th class="px-2 py-2 text-left">Credit</th>
                                <th class="px-2 py-2 text-left">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entry->lines as $line)
                                <tr class="border-t border-slate-100">
                                    <td class="px-2 py-2">{{ $line->ledgerAccount->code }} — {{ $line->ledgerAccount->name }}</td>
                                    <td class="px-2 py-2">{{ number_format($line->debit_kobo, 0) }}</td>
                                    <td class="px-2 py-2">{{ number_format($line->credit_kobo, 0) }}</td>
                                    <td class="px-2 py-2 text-slate-500">{{ $line->description ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 font-semibold">
                            <tr>
                                <td class="px-2 py-2 text-right">Totals</td>
                                <td class="px-2 py-2">{{ number_format($entry->lines->sum('debit_kobo'), 0) }}</td>
                                <td class="px-2 py-2">{{ number_format($entry->lines->sum('credit_kobo'), 0) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
