<x-layout.app title="Journal Entries | Express Cloud">
    <x-layout.app-shell
        page-title="Journal Entries"
        page-description="Record and manage all manual journal entries."
    >
        @if (session('status'))
            <div class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                    <span class="text-sm text-slate-500">to</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                    <select name="status" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" size="sm">Filter</x-ui.button>
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
                </form>
            </div>
            <a href="{{ route('admin.accounting.journal-entries.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + New Journal Entry
            </a>
        </div>

        <x-ui.card>
            <div class="ec-responsive-table overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Number</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Period</th>
                            <th class="px-3 py-3">Memo</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Created</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-3 font-mono text-xs">{{ $entry->journal_number }}</td>
                                <td class="px-3 py-3">{{ $entry->entry_date->format('Y-m-d') }}</td>
                                <td class="px-3 py-3">{{ $entry->accountingPeriod->name ?? '—' }}</td>
                                <td class="px-3 py-3 max-w-[200px] truncate">{{ $entry->memo }}</td>
                                <td class="px-3 py-3">
                                    <x-ui.status-badge :tone="match($entry->status) {
                                        'draft' => 'neutral',
                                        'posted' => 'success',
                                        'reversed' => 'warning',
                                        default => 'neutral'
                                    }">
                                        {{ ucfirst($entry->status) }}
                                    </x-ui.status-badge>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-500">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                                        View
                                    </a>
                                    @if ($entry->status === 'draft')
                                        <a href="{{ route('admin.accounting.journal-entries.edit', $entry) }}" class="ml-2 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                            Edit
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-sm text-slate-500">
                                    No journal entries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $entries->links() }}
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
