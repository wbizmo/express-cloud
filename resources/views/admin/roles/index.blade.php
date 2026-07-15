<x-layout.app title="Roles and permissions | Express Cloud">
    <x-layout.app-shell
        page-title="Roles and permissions"
        page-description="Create restrained role definitions and assign only the permissions each role requires."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_440px]">
            <x-ui.card title="Role directory">
                <div class="space-y-3">
                    @forelse ($roles as $role)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-semibold text-slate-950">{{ $role->name }}</h2>
                                        @if ($role->is_system)
                                            <x-ui.status-badge tone="info">System role</x-ui.status-badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $role->description }}</p>
                                </div>
                                <div class="text-right text-xs text-slate-500">
                                    <p>{{ $role->accounts_count }} accounts</p>
                                    <p>{{ $role->permissions_count }} permissions</p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No roles configured.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create custom role">
                <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5">
                    @csrf
                    <x-ui.input name="name" label="Role name" required />
                    <x-ui.input name="slug" label="Role slug" required />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" class="min-h-24 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm"></textarea>
                    </label>

                    <div class="max-h-[420px] space-y-5 overflow-y-auto pr-2">
                        @foreach ($permissionGroups as $group => $permissions)
                            <fieldset>
                                <legend class="text-sm font-semibold capitalize text-slate-900">
                                    {{ str_replace('-', ' ', $group) }}
                                </legend>
                                <div class="mt-3 space-y-2">
                                    @foreach ($permissions as $slug => $label)
                                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50">
                                            <input type="checkbox" name="permissions[]" value="{{ $slug }}" class="mt-0.5 rounded border-slate-300">
                                            <span>
                                                <span class="block font-medium text-slate-800">{{ $label }}</span>
                                                <span class="mt-0.5 block font-mono text-[11px] text-slate-400">{{ $slug }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <x-ui.button type="submit" class="w-full">Create role</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
