@props([
    'name',
    'options' => [],
    'placeholder' => 'Select…',
    'required' => false,
    'selected' => null,
])

{{--
    A lightweight, server-rendered searchable dropdown.

    - Options are always rendered from the Blade/PHP array passed in (i.e.
      straight from a DB/Eloquent query in the controller) — never fetched
      client-side, so there is no dependency on a JS request succeeding
      before the list can show anything.
    - The list box caps at roughly 3 rows tall and scrolls beyond that;
      with 1 or 2 options it simply sizes down to fit them, it does not
      reserve empty space for a 3rd row that isn't there.
    - A plain hidden input carries the actual value on submit, so this
      behaves exactly like a native <select required> as far as the form
      is concerned.
--}}
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
            const match = this.options.find(o => o.value === this.value);
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
        x-on:click="open = !open"
        class="flex min-h-11 w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-left"
    >
        <span x-text="selectedLabel || @js($placeholder)" x-bind:class="selectedLabel ? 'text-slate-900' : 'text-slate-400'"></span>
        <span class="text-slate-400">▾</span>
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <div class="border-b border-slate-100 p-2">
            <input
                type="text"
                x-model="query"
                x-ref="search"
                placeholder="Type to filter…"
                class="w-full rounded-md border border-slate-200 px-2.5 py-1.5 text-sm"
            >
        </div>

        {{--
            max-height ≈ 3 rows (each row ~40px incl. padding) + a hair of
            breathing room; overflow-y-auto only kicks in past that, and
            the box naturally shrinks to fit 1–2 rows since nothing forces
            a fixed height below the cap.
        --}}
        <ul class="max-h-[128px] overflow-y-auto py-1" style="scrollbar-gutter: stable;">
            <template x-for="option in filtered" :key="option.value">
                <li>
                    <button
                        type="button"
                        x-on:click="choose(option)"
                        class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                        x-bind:class="value === option.value ? 'bg-slate-50 font-medium' : ''"
                        x-text="option.label"
                    ></button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-slate-400">No matches.</li>
        </ul>
    </div>
</div>
