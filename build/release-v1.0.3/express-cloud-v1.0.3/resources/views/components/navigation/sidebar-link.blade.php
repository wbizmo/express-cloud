@props([
    'label',
    'icon',
    'href' => '#',
    'active' => false,
])
<a
    href="{{ $href }}"
    @class([
        'group flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition',
        'bg-white/12 text-white' => $active,
        'text-slate-300 hover:bg-white/8 hover:text-white' => !$active,
    ])
    @if($active) aria-current="page" @endif
>
    <x-ui.icon :name="$icon" :size="19" />
    <span x-show="!$store.shell.sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">{{ $label }}</span>
</a>
