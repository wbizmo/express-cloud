<div
    x-cloak
    x-show="$store.shell.mobileOpen"
    class="fixed inset-0 z-[80] lg:hidden"
>
    <div
        class="absolute inset-0 bg-slate-950/45"
        x-transition.opacity
        @click="$store.shell.closeMobile()"
    ></div>

    <aside
        x-show="$store.shell.mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="absolute inset-y-0 left-0 flex w-[min(88vw,320px)] flex-col bg-[var(--ec-navy-900)] text-white shadow-[var(--ec-shadow-elevated)]"
    >
        <header class="flex h-16 items-center justify-between border-b border-white/10 px-4">
            <div>
                <p class="text-sm font-semibold">Express Cloud</p>
                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">
                    by Zivora
                </p>
            </div>

            <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white"
                aria-label="Close navigation"
                @click="$store.shell.closeMobile()"
            >
                <x-ui.icon name="x" />
            </button>
        </header>

        <nav class="ec-scrollbar flex-1 overflow-y-auto p-3" aria-label="Mobile navigation">
            @foreach (config('navigation.primary') as $section)
                <section class="mb-5">
                    <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        {{ $section['label'] }}
                    </p>

                    <div class="space-y-1">
                        @foreach ($section['items'] as $item)
                            <x-navigation.sidebar-link
                                :label="$item['label']"
                                :icon="$item['icon']"
                            />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>
    </aside>
</div>
