@props([
    'label',
    'icon',
    'active' => false,
    'href' => '#',
])

<a
    href="{{ $href }}"
    @click.prevent
    :title="$store.shell.sidebarCollapsed ? '{{ $label }}' : ''"
    {{ $attributes->class([
        'group flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition duration-150',
        'bg-white/10 text-white' => $active,
        'text-slate-300 hover:bg-white/8 hover:text-white' => ! $active,
    ]) }}
>
    <x-ui.icon :name="$icon" :size="19" />

    <span
        x-show="!$store.shell.sidebarCollapsed"
        x-transition.opacity.duration.150ms
        class="truncate"
    >
        {{ $label }}
    </span>
</a>
