@props([
    'name',
    'title',
    'description' => null,
    'maxWidth' => 'max-w-lg',
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
>
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[90]"
        aria-modal="true"
        role="dialog"
        aria-labelledby="{{ $name }}-title"
    >
        <div
            class="absolute inset-0 bg-slate-950/45"
            x-transition.opacity
            @click="open = false"
        ></div>

        <div class="relative grid min-h-full place-items-center p-4">
            <section
                x-show="open"
                x-transition
                class="w-full {{ $maxWidth }} rounded-2xl border border-slate-200 bg-white shadow-[var(--ec-shadow-elevated)]"
            >
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div>
                        <h2 id="{{ $name }}-title" class="text-lg font-semibold text-slate-950">
                            {{ $title }}
                        </h2>

                        @if ($description)
                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                {{ $description }}
                            </p>
                        @endif
                    </div>

                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close dialog"
                        @click="open = false"
                    >
                        <x-ui.icon name="x" />
                    </button>
                </header>

                <div class="p-6">
                    {{ $slot }}
                </div>
            </section>
        </div>
    </div>
</div>
