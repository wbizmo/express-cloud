@php
    /** @var \App\Models\Account|null $navigationAccount */
    $navigationAccount = auth()->user();
    $navigation = app(\App\Services\Organisation\NavigationVisibility::class);
    $navigationPreview = app()->environment(['local', 'testing'])
        && request()->routeIs('ui.preview');
    $primarySections = $navigationAccount !== null
        ? $navigation->sections($navigationAccount, config('navigation.primary', []))
        : ($navigationPreview ? config('navigation.primary', []) : []);
    $secondaryItems = $navigationAccount !== null
        ? $navigation->items($navigationAccount, config('navigation.secondary', []))
        : ($navigationPreview ? config('navigation.secondary', []) : []);
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 hidden border-r border-white/10 bg-[var(--ec-navy-900)] text-white transition-[width] duration-200 lg:flex lg:flex-col"
    :class="$store.shell.sidebarCollapsed ? 'w-[72px]' : 'w-[280px]'"
>
    <div class="flex h-16 items-center border-b border-white/10 px-4">
        <a href="{{ route('staff.dashboard') }}" class="flex min-w-0 items-center gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-[var(--ec-navy-900)]"><span class="text-sm font-bold">EC</span></div>
            <div x-show="!$store.shell.sidebarCollapsed" x-transition.opacity.duration.150ms class="min-w-0">
                <p class="truncate text-sm font-semibold">Express Cloud</p>
                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">by Zivora</p>
            </div>
        </a>
    </div>

    <nav class="ec-scrollbar flex-1 overflow-y-auto px-3 py-4" aria-label="Primary navigation">
        @foreach ($primarySections as $section)
            <section class="mb-5">
                <p x-show="!$store.shell.sidebarCollapsed" class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $section['label'] }}</p>
                <div class="space-y-1">
                    @foreach ($section['items'] as $item)
                        <x-navigation.sidebar-link
                            :label="$item['label']"
                            :icon="$item['icon']"
                            :href="route($item['route'])"
                            :active="request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']))"
                        />
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <div class="space-y-1">
            @foreach ($secondaryItems as $item)
                <x-navigation.sidebar-link :label="$item['label']" :icon="$item['icon']" :href="route($item['route'])" :active="request()->routeIs($item['route'])" />
            @endforeach
        </div>
        <button type="button" class="mt-3 flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-slate-300 hover:bg-white/8 hover:text-white" @click="$store.shell.toggleSidebar()">
            <x-ui.icon name="panel-left-close" :size="19" x-show="!$store.shell.sidebarCollapsed" />
            <x-ui.icon name="panel-left-open" :size="19" x-show="$store.shell.sidebarCollapsed" />
            <span x-show="!$store.shell.sidebarCollapsed">Collapse</span>
        </button>
    </div>
</aside>
