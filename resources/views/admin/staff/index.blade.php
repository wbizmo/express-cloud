<x-layout.app title="Staff | Express Cloud">
    <x-layout.app-shell
        page-title="Staff accounts"
        page-description="Create accounts, assign roles and branches, and manage access without exposing credentials in general lists."
    >
        @if (session('revealed_access_key'))
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm font-semibold text-amber-900">Access key for {{ session('revealed_access_key.name') }}</p>
                <p class="mt-2 break-all font-mono text-xl font-bold tracking-[0.1em] text-amber-950">{{ session('revealed_access_key.key') }}</p>
                <p class="mt-2 text-xs text-amber-800">This reveal was permission-checked and written to the audit log.</p>
            </div>
        @endif

        @if (session('generated_access_key'))
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <p class="text-sm font-semibold text-blue-900">New access key</p>
                <p class="mt-2 font-mono text-2xl font-bold tracking-[0.14em] text-blue-950">
                    {{ session('generated_access_key') }}
                </p>
                <p class="mt-2 text-xs text-blue-700">
                    Record this key securely. It will remain viewable only through authorised account controls.
                </p>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <x-ui.card title="Staff directory">
                <div class="space-y-3">
                    @forelse ($accounts as $account)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="grid h-10 w-10 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                                            {{ $account->initials() }}
                                        </div>
                                        <div>
                                            <h2 class="font-semibold text-slate-950">{{ $account->displayName() }}</h2>
                                            <p class="text-xs text-slate-500">
                                                {{ $account->roles->pluck('name')->implode(', ') ?: 'No role assigned' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-500">
                                        @if ($account->is_allowed_all_branches)
                                            All branches
                                        @else
                                            {{ $account->branches->pluck('name')->implode(', ') ?: 'No branch assigned' }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.status-badge :tone="$account->status->value === 'active' ? 'success' : 'warning'">
                                        {{ ucfirst($account->status->value) }}
                                    </x-ui.status-badge>
                                    @if (auth()->user() && app(\App\Services\Organisation\AuthorizationService::class)->hasPermission(auth()->user(), 'staff.access-key.reveal'))
                                        <form method="POST" action="{{ route('admin.staff.access-key.reveal', $account) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="ghost">Reveal key</x-ui.button>
                                        </form>
                                    @endif
                                    @if ($account->status->value === 'active')
                                        <form method="POST" action="{{ route('admin.staff.suspend', $account) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.button type="submit" variant="ghost">Suspend</x-ui.button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No staff accounts found.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create staff account">
                <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-[repeat(2,minmax(0,1fr))]">
                        <x-ui.input name="first_name" label="First name" required />
                        <x-ui.input name="last_name" label="Last name" required />
                    </div>
                    <x-ui.input name="email" type="email" label="Email address" help="Stored encrypted. Not shown on the login screen." />

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-slate-700">Roles</legend>
                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-slate-300">
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_allowed_all_branches" value="1" class="rounded border-slate-300">
                        Allow access to all branches
                    </label>

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-slate-700">Specific branches</legend>
                        <div class="max-h-44 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                            @foreach ($branches as $branch)
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="rounded border-slate-300">
                                    {{ $branch->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-ui.button type="submit" class="w-full">Create staff account</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
