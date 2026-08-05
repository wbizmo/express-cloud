@props([
    'name',
    'options' => [],
    'placeholder' => 'Select…',
    'required' => false,
    'selected' => null,
])

<div
    x-data="{
        open: false,
        query: '',
        value: @js($selected),
        options: @js(collect($options)->values()->all()),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (q === '') return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        get selectedLabel() {
            const match = this.options.find(o => String(o.value) === String(this.value));
            return match ? match.label : '';
        },
        choose(option) {
            this.value = option.value;
            this.query = '';
            this.open = false;
        },
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="value" @if($required) required @endif>

    <button
        type="button"
        x-on:click="open = !open; if (open) $nextTick(() => $refs.search?.focus())"
        class="flex min-h-11 w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3.5 text-left text-sm transition hover:border-slate-400"
        x-bind:aria-expanded="open"
    >
        <span x-text="selectedLabel || @js($placeholder)" x-bind:class="selectedLabel ? 'text-slate-900' : 'text-slate-400'"></span>
        <x-ui.icon name="chevron-down" :size="18" class="text-slate-400 transition" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 p-2">
            <div class="relative">
                <x-ui.icon name="search" :size="17" class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    type="text"
                    x-model="query"
                    x-ref="search"
                    placeholder="Type to filter…"
                    class="w-full rounded-md border border-slate-200 py-2 pl-8 pr-2.5 text-sm"
                >
            </div>
        </div>
        <ul class="ec-scrollbar max-h-[168px] overflow-y-auto py-1" style="scrollbar-gutter: stable;">
            <template x-for="option in filtered" :key="option.value">
                <li>
                    <button
                        type="button"
                        x-on:click="choose(option)"
                        class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-slate-50"
                        x-bind:class="String(value) === String(option.value) ? 'bg-blue-50 font-semibold text-blue-800' : ''"
                    >
                        <span x-text="option.label" class="min-w-0 truncate"></span>
                        <x-ui.icon name="check" :size="16" x-show="String(value) === String(option.value)" class="text-blue-700" />
                    </button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-3 text-sm text-slate-400">No matches.</li>
        </ul>
    </div>
</div>
