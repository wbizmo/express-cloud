<x-layout.app title="Alert recipients | Express Cloud">
    <x-layout.app-shell
        page-title="Alert recipients"
        page-description="Every active address receives the end-of-day operations digest."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_400px]">
            <x-ui.card title="Recipients">
                <div class="space-y-3">
                    @forelse ($recipients as $recipient)
                        <article class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4">
                            <div>
                                <h2 class="font-semibold text-slate-950">{{ $recipient->email }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $recipient->label ?: 'No label' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.operations.alert-recipients.toggle', $recipient) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.button type="submit" variant="secondary">
                                    {{ $recipient->is_active ? 'Disable' : 'Enable' }}
                                </x-ui.button>
                            </form>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No alert recipients configured.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Add recipient">
                <form method="POST" action="{{ route('admin.operations.alert-recipients.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="email" type="email" label="Email address" required />
                    <x-ui.input name="label" label="Label" placeholder="Owner, Operations Manager" />
                    <x-ui.button type="submit" class="w-full">Add recipient</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
