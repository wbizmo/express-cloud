<x-layout.app title="Activity Log | Express Cloud">
    <x-layout.app-shell
        page-title="Activity Log"
        page-description="Every recorded action across the system, who did it, and when."
    >
        <form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-5">
            <x-ui.input name="from" type="date" label="From" :value="request('from')" />
            <x-ui.input name="to" type="date" label="To" :value="request('to')" />

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Branch</span>
                <select name="branch" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(request('branch') === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Type</span>
                <select name="entity_type" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                    <option value="">All types</option>
                    @foreach ($entityTypes as $type)
                        <option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end">
                <x-ui.button type="submit" class="w-full">Filter</x-ui.button>
            </div>
        </form>

        @can('activity.export')
            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('admin.reports.exports.audit', request()->query()) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">Export CSV</a>
            </div>
        @endcan

        <x-ui.card title="Recorded actions">
            <div class="ec-responsive-table overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'occurred_at', 'direction' => $sort === 'occurred_at' && $direction === 'desc' ? 'asc' : 'desc']) }}">
                                    Time {{ $sort === 'occurred_at' ? ($direction === 'desc' ? '↓' : '↑') : '' }}
                                </a>
                            </th>
                            <th class="px-3 py-3">Branch</th>
                            <th class="px-3 py-3">Staff</th>
                            <th class="px-3 py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'action', 'direction' => $sort === 'action' && $direction === 'desc' ? 'asc' : 'desc']) }}">
                                    Action {{ $sort === 'action' ? ($direction === 'desc' ? '↓' : '↑') : '' }}
                                </a>
                            </th>
                            <th class="px-3 py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'entity_type', 'direction' => $sort === 'entity_type' && $direction === 'desc' ? 'asc' : 'desc']) }}">
                                    Type {{ $sort === 'entity_type' ? ($direction === 'desc' ? '↓' : '↑') : '' }}
                                </a>
                            </th>
                            <th class="px-3 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-3 whitespace-nowrap">{{ $log->occurred_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-3">{{ $log->branch?->name ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $log->actor_name ?? trim(($log->actor?->first_name ?? '').' '.($log->actor?->last_name ?? '')) ?: '—' }}</td>
                                <td class="px-3 py-3 font-medium">{{ $log->action }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ $log->entity_type }} @if($log->entity_id)<span class="font-mono text-xs">#{{ mb_substr($log->entity_id, 0, 8) }}</span>@endif</td>
                                <td class="px-3 py-3 text-xs text-slate-400">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-slate-500">No activity recorded for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
