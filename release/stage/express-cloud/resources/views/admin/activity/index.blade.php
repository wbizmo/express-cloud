<x-layout.app title="Activity log | Express Cloud">
<x-layout.app-shell page-title="System activity" page-description="Filterable append-only record of meaningful write actions.">
<form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
<label class="block"><span class="mb-2 block text-sm font-medium text-slate-700">Actor</span><select name="actor" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">All actors</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected(request('actor')===$actor->id)>{{ trim($actor->first_name.' '.$actor->last_name) }}</option>@endforeach</select></label>
<x-ui.input name="entity_type" label="Entity type" :value="request('entity_type')" />
<x-ui.input name="from" type="date" label="From" :value="request('from')" />
<x-ui.input name="to" type="date" label="To" :value="request('to')" />
<div class="md:col-span-4"><x-ui.button type="submit">Apply filters</x-ui.button></div>
</form>
<x-ui.card title="Activity">
<div class="space-y-3">@forelse($entries as $entry)<article class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap justify-between gap-3"><strong>{{ $entry->action ?? $entry->event ?? 'Activity' }}</strong><span class="text-sm text-slate-500">{{ $entry->created_at }}</span></div><p class="mt-2 text-sm text-slate-600">{{ $entry->entity_type ?? 'system' }} · {{ $entry->entity_id ?? '—' }}</p></article>@empty<p class="text-sm text-slate-500">No activity matches these filters.</p>@endforelse</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
