<x-layout.app>
    <x-layout.app-shell page-title="Lisa AI" page-description="Permission-safe business analysis generated from summarized operational records. Lisa does not execute arbitrary SQL or receive access keys and credentials.">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.insights.generate') }}" class="flex flex-wrap items-end gap-2">@csrf
                <label class="text-xs text-slate-500">From<input class="ml-2 rounded-lg border px-3 py-2" type="date" name="from" value="{{ $from }}" required></label>
                <label class="text-xs text-slate-500">To<input class="ml-2 rounded-lg border px-3 py-2" type="date" name="to" value="{{ $to }}" required></label>
                <x-ui.button type="submit">Refresh insights</x-ui.button>
            </form>
        </x-slot:actions>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($insights as $insight)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $insight->category }} · {{ $insight->severity }}</p><h2 class="mt-1 text-lg font-semibold">{{ $insight->title }}</h2></div>
                        <form method="POST" action="{{ route('admin.insights.dismiss', $insight) }}">@csrf @method('PATCH')<button class="text-xs font-medium text-slate-500 hover:text-slate-900">Dismiss</button></form>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $insight->summary }}</p>
                    @if($insight->recommendation)<div class="mt-4 rounded-lg bg-slate-50 p-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recommended action</p><p class="mt-1 text-sm text-slate-700">{{ $insight->recommendation }}</p></div>@endif
                    <p class="mt-3 text-xs text-slate-400">Generated {{ $insight->generated_at?->diffForHumans() }} · {{ $insight->period_start?->toDateString() }} to {{ $insight->period_end?->toDateString() }}</p>
                </x-ui.card>
            @empty
                <x-ui.card><p class="text-sm text-slate-500">No insight has been generated for this period. Use Refresh insights.</p></x-ui.card>
            @endforelse
        </div>
        <div class="mt-5">{{ $insights->links() }}</div>
    </x-layout.app-shell>
</x-layout.app>
