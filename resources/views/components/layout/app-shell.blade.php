@props([
    'pageTitle' => 'Dashboard',
    'pageDescription' => null,
])

<div
    x-data
    class="min-h-screen bg-[var(--ec-background)]"
>
    <x-feedback.page-progress />
    <x-navigation.sidebar />
    <x-navigation.mobile-drawer />

    <div
        class="min-h-screen transition-[padding] duration-200"
        :class="$store.shell.sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-[280px]'"
    >
        <x-navigation.topbar />

        <main class="px-4 py-6 sm:px-6 lg:px-6">
            <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <nav class="mb-2 text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                        Express Cloud / Workspace
                    </nav>

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
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        {{ $actions }}
                    </div>
                @endisset
            </header>

            {{ $slot }}
        </main>
    </div>

    <x-feedback.toast-region />
</div>
