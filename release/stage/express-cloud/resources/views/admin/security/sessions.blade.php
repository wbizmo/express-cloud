<x-layout.app title="Live sessions | Express Cloud">
<x-layout.app-shell page-title="Live sessions" page-description="Active and recent terminal sessions with immediate force-termination.">
<x-ui.card title="Sessions">
<div class="space-y-3">@forelse($sessions as $session)<article class="flex flex-col gap-4 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-slate-950">{{ $session->account?->displayName() }}</h2><p class="mt-1 text-sm text-slate-500">{{ $session->ip_address ?? 'Unknown IP' }} · Last activity {{ $session->last_activity_at ?? 'Unknown' }}</p></div>@if(empty($session->revoked_at) && empty($session->ended_at))<form method="POST" action="{{ route('admin.security.sessions.destroy',$session) }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary">Terminate</x-ui.button></form>@else<x-ui.status-badge tone="neutral">Ended</x-ui.status-badge>@endif</article>@empty<p class="text-sm text-slate-500">No sessions recorded.</p>@endforelse</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
