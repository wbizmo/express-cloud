<x-layout.app title="Active sessions | Express Cloud">
    <x-layout.app-shell
        page-title="Active sessions"
        page-description="Review currently active staff sessions and revoke individual sessions when required."
    >
        <x-ui.card title="Session activity">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Staff</th>
                            <th class="px-3 py-3">IP address</th>
                            <th class="px-3 py-3">Device</th>
                            <th class="px-3 py-3">Last activity</th>
                            <th class="px-3 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-3 py-4 font-medium text-slate-950">
                                    {{ $session->account?->displayName() ?? 'Deleted account' }}
                                </td>
                                <td class="px-3 py-4 font-mono text-xs text-slate-600">
                                    {{ $session->ip_address ?? 'Unavailable' }}
                                </td>
                                <td class="max-w-xs truncate px-3 py-4 text-slate-600">
                                    {{ $session->user_agent ?? 'Unavailable' }}
                                </td>
                                <td class="px-3 py-4 text-slate-600">
                                    {{ $session->last_activity_at?->diffForHumans() }}
                                </td>
                                <td class="px-3 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.security.sessions.revoke', $session) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.button type="submit" variant="danger">
                                            Revoke
                                        </x-ui.button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-10 text-center text-slate-500">
                                    No active sessions.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
