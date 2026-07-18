@props([
    'variant' => 'primary',
    'type' => 'button',
    'icon' => null,
    'loading' => false,
    'loadingText' => 'Working…',
])

@php
    $base = 'inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition duration-200 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-950',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
    ];
@endphp

<button
    type="{{ $type }}"
    @if ($type === 'submit') data-submit-button @endif
    {{ $attributes->class([$base, $variants[$variant] ?? $variants['primary']]) }}
>
    @if ($loading)
        <span class="ec-spinner" aria-hidden="true"></span>
        <span>{{ $loadingText }}</span>
    @else
        @if ($icon)
            <x-ui.icon :name="$icon" />
        @endif

        <span>{{ $slot }}</span>
    @endif
</button>
