@props([
    'name',
    'size' => 18,
])

<i
    data-lucide="{{ $name }}"
    width="{{ $size }}"
    height="{{ $size }}"
    {{ $attributes->class('shrink-0') }}
></i>
