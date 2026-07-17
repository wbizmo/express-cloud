@props([
    'code',
    'title',
    'message',
    'reference' => null,
])

<x-layout.app :title="$title.' | Express Cloud'">
    <main class="grid min-h-screen place-items-center bg-slate-50 p-6">
        <section class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold text-blue-700">
                Error {{ $code }}
            </p>

            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                {{ $title }}
            </h1>

            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
                {{ $message }}
            </p>

            @if ($reference)
                <p class="mt-5 font-mono text-xs text-slate-400">
                    Reference: {{ $reference }}
                </p>
            @endif

            <div class="mt-7 flex justify-center">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Return to sign in
                </a>
            </div>
        </section>
    </main>
</x-layout.app>
