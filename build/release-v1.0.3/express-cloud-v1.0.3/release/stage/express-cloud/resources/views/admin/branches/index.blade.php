<x-layout.app title="Branches | Express Cloud">
    <x-layout.app-shell
        page-title="Branches"
        page-description="Manage physical operating locations without removing historical records."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <x-ui.card title="Branch directory">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Branch</th>
                                <th class="px-3 py-3">Code</th>
                                <th class="px-3 py-3">Address</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($branches as $branch)
                                <tr>
                                    <td class="px-3 py-4 font-medium text-slate-950">
                                        {{ $branch->name }}
                                        @if ($branch->is_head_office)
                                            <x-ui.status-badge tone="info">Head office</x-ui.status-badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 font-mono text-xs text-slate-600">{{ $branch->code }}</td>
                                    <td class="px-3 py-4 text-slate-600">{{ $branch->address }}</td>
                                    <td class="px-3 py-4">
                                        <x-ui.status-badge :tone="$branch->status->value === 'active' ? 'success' : 'neutral'">
                                            {{ ucfirst($branch->status->value) }}
                                        </x-ui.status-badge>
                                    </td>
                                    <td class="px-3 py-4 text-right">
                                        @if ($branch->status->value === 'active' && ! $branch->is_head_office)
                                            <form method="POST" action="{{ route('admin.branches.deactivate', $branch) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.button type="submit" variant="ghost">
                                                    Deactivate
                                                </x-ui.button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-10 text-center text-slate-500">
                                        No branches have been configured.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="Add branch" description="Branch codes are uppercase and unique.">
                <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="name" label="Branch name" required />
                    <x-ui.input name="code" label="Branch code" required />
                    <x-ui.input name="phone" label="Phone" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Address</span>
                        <textarea name="address" required class="min-h-28 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100"></textarea>
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_head_office" value="1" class="rounded border-slate-300">
                        Set as head office
                    </label>
                    <x-ui.button type="submit" class="w-full">Create branch</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
