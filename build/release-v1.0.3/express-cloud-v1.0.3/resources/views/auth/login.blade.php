<x-layout.app title="Sign in | Express Cloud">
    <main
        x-data="{
            query: '',
            results: [],
            selected: null,
            searching: false,
            open: false,
            async search() {
                if (this.query.trim().length < 2) {
                    this.results = [];
                    this.open = false;
                    return;
                }

                this.searching = true;

                try {
                    const response = await fetch(
                        '{{ route('login.staff-search') }}?q='
                        + encodeURIComponent(this.query),
                        {
                            headers: {
                                Accept: 'application/json',
                            },
                        },
                    );

                    const payload = await response.json();
                    this.results = payload.data ?? [];
                    this.open = true;
                } finally {
                    this.searching = false;
                    ExpressCloud.refreshIcons();
                }
            },
            choose(account) {
                this.selected = account;
                this.query = account.name;
                this.open = false;
            },
        }"
        class="grid min-h-screen bg-slate-50 lg:grid-cols-[1fr_520px]"
    >
        <section class="hidden bg-[var(--ec-navy-900)] p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <p class="text-xl font-semibold">Express Cloud</p>
                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                    by Zivora
                </p>
            </div>

            <div class="max-w-xl">
                <p class="text-sm font-medium text-blue-200">
                    Business operations, clearly managed.
                </p>
                <h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight">
                    Sales, invoicing, inventory, and branch operations in one
                    focused workspace.
                </h1>
                <p class="mt-5 max-w-lg text-sm leading-7 text-slate-300">
                    Select your staff name and enter your assigned access key
                    to continue.
                </p>
            </div>

            <p class="text-xs text-slate-500">
                Secure access for authorised company staff only.
            </p>
        </section>

        <section class="grid min-h-screen place-items-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <p class="text-lg font-semibold text-slate-950">Express Cloud</p>
                    <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">
                        by Zivora
                    </p>
                </div>

                <header>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                        Sign in
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Search for your name, then enter your assigned access key.
                    </p>
                </header>

                @if (session('status'))
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('auth_error'))
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('auth_error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div class="relative">
                        <label for="staff-search" class="mb-2 block text-sm font-medium text-slate-700">
                            Staff member
                        </label>

                        <div class="relative">
                            <x-ui.icon
                                name="search"
                                :size="17"
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                            />

                            <input
                                id="staff-search"
                                type="search"
                                autocomplete="off"
                                placeholder="Type your first or last name"
                                x-model="query"
                                x-on:input.debounce.250ms="search()"
                                x-on:focus="if (results.length) open = true"
                                class="min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-950 placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                            >

                            <span
                                x-cloak
                                x-show="searching"
                                class="ec-spinner absolute right-3 top-1/2 -translate-y-1/2 text-blue-600"
                            ></span>
                        </div>

                        <input
                            type="hidden"
                            name="account_public_id"
                            :value="selected?.id ?? ''"
                        >

                        <div
                            x-cloak
                            x-show="open"
                            x-on:click.outside="open = false"
                            class="ec-scrollbar absolute z-30 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-[var(--ec-shadow-elevated)]"
                        >
                            <template x-if="results.length === 0 && !searching">
                                <p class="px-3 py-4 text-sm text-slate-500">
                                    No matching active staff member found.
                                </p>
                            </template>

                            <template x-for="account in results" :key="account.id">
                                <button
                                    type="button"
                                    class="flex min-h-12 w-full items-center gap-3 rounded-lg px-3 text-left hover:bg-slate-100"
                                    x-on:click="choose(account)"
                                >
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700" x-text="account.initials"></span>
                                    <span class="min-w-0 truncate text-sm font-medium text-slate-900" x-text="account.name"></span>
                                </button>
                            </template>
                        </div>

                        @error('account_public_id')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="access-key" class="mb-2 block text-sm font-medium text-slate-700">
                            Access key
                        </label>

                        <input
                            id="access-key"
                            type="text"
                            name="access_key"
                            inputmode="text"
                            autocomplete="one-time-code"
                            maxlength="9"
                            placeholder="ECBV-YKQW"
                            value="{{ old('access_key') }}"
                            x-data
                            x-on:input="
                                let raw = $el.value
                                    .toUpperCase()
                                    .replace(/[^A-HJ-KM-NP-Z]/g, '')
                                    .slice(0, 8);

                                $el.value = raw.length > 4
                                    ? raw.slice(0, 4) + '-' + raw.slice(4)
                                    : raw;
                            "
                            class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-4 font-mono text-lg font-semibold tracking-[0.14em] text-slate-950 placeholder:text-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                        >

                        @error('access_key')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" class="w-full">
                        Sign in
                    </x-ui.button>
                </form>

                <p class="mt-8 text-center text-xs leading-5 text-slate-400">
                    Access is restricted to authorised users of this company.
                </p>
            </div>
        </section>
    </main>
</x-layout.app>
