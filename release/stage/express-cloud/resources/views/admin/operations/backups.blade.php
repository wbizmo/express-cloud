<x-layout.app title="Backups | Express Cloud">
<x-layout.app-shell page-title="Backups and recovery" page-description="Checksummed database archives with verification history and explicit retention.">
<div data-page-header class="mb-5 flex justify-end">
<form method="POST" action="{{ route('admin.operations.backups.store') }}">
@csrf
<x-ui.button type="submit">Create backup now</x-ui.button>
</form>
</div>

<x-ui.card title="Backup runs">
<div class="space-y-3">
@forelse ($runs as $run)
<article class="rounded-xl border border-slate-200 p-4">
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
<div>
<h2 class="font-semibold text-slate-950">{{ $run->id }}</h2>
<p class="mt-1 text-sm text-slate-500">{{ ucfirst($run->status) }} · {{ $run->started_at?->format('d M Y H:i:s') }}</p>
@if ($run->path)<p class="mt-1 font-mono text-xs text-slate-500">{{ $run->path }}</p>@endif
@if ($run->checksum_sha256)<p class="mt-1 font-mono text-xs text-slate-500">SHA-256 {{ $run->checksum_sha256 }}</p>@endif
@if ($run->failure_message)<p class="mt-2 text-sm text-red-700">{{ $run->failure_message }}</p>@endif
</div>
@if ($run->status === 'completed')
<form method="POST" action="{{ route('admin.operations.backups.verify', $run) }}">
@csrf
<x-ui.button type="submit" variant="secondary">Verify integrity</x-ui.button>
</form>
@endif
</div>
</article>
@empty
<p class="text-sm text-slate-500">No backups have been created.</p>
@endforelse
</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
