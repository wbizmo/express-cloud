@props([
    'pageTitle' => 'Dashboard',
    'pageDescription' => null,
])

<div
    x-data
    class="ec-app-frame min-h-screen max-w-full overflow-x-clip bg-[var(--ec-background)]"
>
    <x-feedback.page-progress />
    <x-navigation.sidebar />
    <x-navigation.mobile-drawer />

    <div
        class="ec-page-content min-h-screen max-w-full overflow-x-clip transition-[padding] duration-200"
        :class="$store.shell.sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-[280px]'"
    >
        <div style="--ec-sidebar-offset: 280px" :style="'--ec-sidebar-offset: ' + (.shell.sidebarCollapsed ? '72px' : '280px')"><div style="--ec-sidebar-offset: 280px" :style="'--ec-sidebar-offset: ' + (.shell.sidebarCollapsed ? '72px' : '280px')"><x-navigation.topbar /></div></div>

        <main class="ec-page-main w-full pt-20 pt-20 max-w-full overflow-x-clip px-4 py-6 sm:px-6 lg:px-6">
            <header class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="mb-3 flex min-w-0 flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="ec-back-button"
                            x-on:click="window.history.length > 1 ? window.history.back() : window.location.assign(@js(route(request()->routeIs('admin.*') ? 'admin.dashboard' : 'staff.dashboard')))"
                        >
                            <x-ui.icon name="arrow-left" :size="16" />
                            <span>Back</span>
                        </button>
                        <nav class="text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                            Express Cloud / Workspace
                        </nav>
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-[2rem]">
                        {{ $pageTitle }}
                    </h1>

                    @if ($pageDescription)
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            {{ $pageDescription }}
                        </p>
                    @endif
                </div>

                @isset($actions)
                    <div class="flex min-w-0 shrink-0 flex-wrap items-center gap-2">
                        {{ $actions }}
                    </div>
                @endisset
            </header>

            {{ $slot }}
        </main>
    </div>

    <x-feedback.toast-region />
</div>
