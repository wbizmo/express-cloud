@props([
    'label' => null,
    'name',
    'type' => 'text',
    'required' => false,
    'help' => null,
])

<label class="block">
    @if ($label)
        <span class="mb-2 block text-sm font-medium text-slate-700">
            {{ $label }}

            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </span>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->class('min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-950 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100') }}
    >

    @if ($help)
        <span class="mt-2 block text-xs leading-5 text-slate-500">
            {{ $help }}
        </span>
    @endif
</label>
