@props([
    'variant' => 'primary',
    'type' => 'button',
    'icon' => null,
    'loading' => false,
    'loadingText' => 'Working…',
])

@php
    $base = 'ec-button inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition duration-200 disabled:cursor-not-allowed disabled:opacity-60';
    $variants = [
        'primary' => 'ec-button-primary',
        'secondary' => 'ec-button-secondary',
        'ghost' => 'ec-button-ghost',
        'danger' => 'ec-button-danger',
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
