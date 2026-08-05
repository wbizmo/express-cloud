@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->class('ec-card') }}>
    @if ($title || $description)
        <header class="ec-card-header">
            @if ($title)
                <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </header>
    @endif
    {{ $slot }}
</section>
