<x-layout.app title="Activity log | Express Cloud">
    <x-layout.app-shell page-title="System activity" page-description="Filterable append-only record of meaningful write actions.">
        <form method="GET" class="mb-6 grid min-w-0 gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <label class="block min-w-0">
                <span class="mb-2 block text-sm font-medium text-slate-700">Actor</span>
                <select name="actor" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm">
                    <option value="">All actors</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected(request('actor') === $actor->id)>
                            {{ trim($actor->first_name.' '.$actor->last_name) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <x-ui.input name="entity_type" label="Entity type" :value="request('entity_type')" />
            <x-ui.input name="from" type="date" label="From" :value="request('from')" />
            <x-ui.input name="to" type="date" label="To" :value="request('to')" />
            <div class="md:col-span-4"><x-ui.button type="submit">Apply filters</x-ui.button></div>
        </form>

        <x-ui.card title="Activity">
            <div class="space-y-3">
                @forelse ($entries as $entry)
                    @php
                        $actorName = trim(($entry->actor_first_name ?? '').' '.($entry->actor_last_name ?? ''));
                        $actorName = $actorName !== '' ? $actorName : ($entry->actor_name ?? 'System');
                        $actorLabel = filled($entry->actor_branch_name ?? null)
                            ? $actorName.' ['.$entry->actor_branch_name.']'
                            : $actorName;
                    @endphp
                    <article class="min-w-0 rounded-xl border border-slate-200 p-4">
                        <div class="flex min-w-0 flex-wrap justify-between gap-3">
                            <div class="min-w-0">
                                <strong class="block break-words">{{ $entry->action ?? $entry->event ?? 'Activity' }}</strong>
                                <p class="mt-1 break-words text-sm font-medium text-slate-700">{{ $actorLabel }}</p>
                            </div>
                            <span class="shrink-0 text-sm text-slate-500">{{ $entry->created_at }}</span>
                        </div>
                        <p class="mt-2 break-all text-sm text-slate-600">
                            {{ $entry->entity_type ?? 'system' }} · {{ $entry->entity_id ?? '—' }}
                        </p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No activity matches these filters.</p>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $entries->withQueryString()->links() }}
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
