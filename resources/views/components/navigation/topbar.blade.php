<header
    class="ec-topbar fixed left-0 right-0 top-0 z-40 lg:left-[var(--ec-sidebar-offset,280px)] lg:left-[var(--ec-sidebar-offset,280px)] flex h-16 w-full max-w-full items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6"
>
    <button
        type="button"
        class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden"
        aria-label="Open navigation"
        @click="$store.shell.openMobile()"
    >
        <x-ui.icon name="menu" />
    </button>

    <div class="hidden min-w-0 flex-1 sm:block">
        <label class="relative block max-w-xl">
            <span class="sr-only">Search Express Cloud</span>
            <x-ui.icon
                name="search"
                :size="17"
                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            />
            <input
                type="search"
                placeholder="Search records, pages, or actions"
                class="h-10 w-full rounded-lg border border-slate-300 bg-slate-50 pl-10 pr-4 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100"
            >
        </label>
    </div>

    <div class="ml-auto flex items-center gap-1.5">
        <button
            type="button"
            class="grid h-10 w-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            aria-label="Notifications"
        >
            <x-ui.icon name="bell" />
        </button>

        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                class="flex min-h-11 items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-100"
                aria-haspopup="menu"
                :aria-expanded="open"
                @click="open = !open"
                @click.outside="open = false"
            >
                @auth
                    @if (auth()->user()->profile_picture_path)
                        <img
                            src="{{ Storage::disk(config('authentication.profile_picture.disk'))->url(auth()->user()->profile_picture_path) }}"
                            alt="{{ auth()->user()->displayName() }}"
                            class="h-8 w-8 rounded-full object-cover"
                        >
                    @else
                        <div class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                @else
                    <div class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                        EC
                    </div>
                @endauth

                <div class="hidden text-left md:block">
                    <p class="max-w-40 truncate text-sm font-semibold text-slate-900">
                        {{ auth()->user()?->displayName() ?? 'Express Cloud user' }}
                    </p>
                    <p class="text-xs text-slate-500">Authorised account</p>
                </div>

                <x-ui.icon name="chevron-down" :size="16" class="hidden text-slate-400 md:block" />
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition
                class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-[var(--ec-shadow-elevated)]"
                role="menu"
            >
                <div class="border-b border-slate-100 px-3 py-2 md:hidden">
                    <p class="truncate text-sm font-semibold text-slate-950">
                        {{ auth()->user()?->displayName() ?? 'Express Cloud user' }}
                    </p>
                    <p class="text-xs text-slate-500">Authorised account</p>
                </div>

                <a
                    href="{{ route('staff.profile.show') }}"
                    class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 text-left text-sm text-slate-700 hover:bg-slate-100"
                    role="menuitem"
                >
                    <x-ui.icon name="user-round" :size="17" />
                    My profile
                </a>

                <button
                    type="button"
                    class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 text-left text-sm text-slate-700 hover:bg-slate-100"
                    role="menuitem"
                >
                    <x-ui.icon name="monitor-smartphone" :size="17" />
                    Active sessions
                </button>

                <div class="my-1 border-t border-slate-100"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium text-red-700 hover:bg-red-50"
                        role="menuitem"
                    >
                        <x-ui.icon name="log-out" :size="17" />
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
