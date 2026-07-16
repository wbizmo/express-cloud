<x-layout.app title="API tokens | Express Cloud">
<x-layout.app-shell page-title="API tokens" page-description="Create revocable, hashed bearer tokens with narrowly scoped abilities.">
<div data-page-header class="mb-5"></div>

@if (session('new_api_token'))
<x-ui.card title="Copy this token now" class="mb-6">
<p class="text-sm text-slate-600">The plaintext token is shown once and cannot be recovered later.</p>
<pre class="mt-4 overflow-x-auto rounded-lg bg-slate-950 p-4 text-sm text-white">{{ session('new_api_token') }}</pre>
</x-ui.card>
@endif

<div class="grid gap-6 xl:grid-cols-[420px_1fr]">
<x-ui.card title="Create API token">
<form method="POST" action="{{ route('admin.api.tokens.store') }}" class="space-y-4">
@csrf
<x-ui.input name="name" label="Token name" required />
<x-ui.input name="expires_at" type="datetime-local" label="Expires at" />
<fieldset>
<legend class="mb-3 text-sm font-semibold text-slate-700">Abilities</legend>
<div class="space-y-3">
@foreach ($abilities as $ability => $label)
<input type="checkbox" name="abilities[]" value="{{ $ability }}" data-label="{{ $label }}">
@endforeach
</div>
</fieldset>
<x-ui.button type="submit">Create token</x-ui.button>
</form>
</x-ui.card>

<x-ui.card title="Issued tokens">
<div class="space-y-3">
@forelse ($tokens as $token)
<article class="flex flex-col gap-4 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
<div>
<h2 class="font-semibold text-slate-950">{{ $token->name }}</h2>
<p class="mt-1 font-mono text-xs text-slate-500">{{ $token->token_prefix }}••••••</p>
<p class="mt-1 text-sm text-slate-500">{{ implode(', ', $token->abilities) }} · Last used {{ $token->last_used_at?->diffForHumans() ?? 'never' }}</p>
</div>
@if ($token->revoked_at === null)
<form method="POST" action="{{ route('admin.api.tokens.destroy', $token) }}">
@csrf
@method('DELETE')
<x-ui.button type="submit" variant="secondary">Revoke</x-ui.button>
</form>
@else
<span class="text-sm font-semibold text-red-700">Revoked</span>
@endif
</article>
@empty
<p class="text-sm text-slate-500">No API tokens created.</p>
@endforelse
</div>
</x-ui.card>
</div>
</x-layout.app-shell>
</x-layout.app>
