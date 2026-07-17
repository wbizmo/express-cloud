@props([
    'name',
    'title',
    'width' => 'max-w-md',
])

<div
    x-data="{ open: false }"
    x-on:open-drawer.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-drawer.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
>
    <div x-cloak x-show="open" class="fixed inset-0 z-[95]">
        <div
            class="absolute inset-0 bg-slate-950/45"
            x-transition.opacity
            @click="open = false"
        ></div>

        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full {{ $width }} flex-col border-l border-slate-200 bg-white shadow-[var(--ec-shadow-elevated)]"
        >
            <header class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
                <h2 class="font-semibold text-slate-950">{{ $title }}</h2>

                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close panel"
                    @click="open = false"
                >
                    <x-ui.icon name="x" />
                </button>
            </header>

            <div class="ec-scrollbar flex-1 overflow-y-auto p-5">
                {{ $slot }}
            </div>
        </aside>
    </div>
</div>
