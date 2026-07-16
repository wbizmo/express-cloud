@props([
    'height' => 'h-4',
    'width' => 'w-full',
])

<div
    aria-hidden="true"
    {{ $attributes->class(["ec-skeleton rounded-md", $height, $width]) }}
></div>
