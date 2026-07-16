<x-layout.app title="Interface Preview | Express Cloud">
    <x-layout.app-shell
        page-title="Operations overview"
        page-description="A restrained enterprise workspace showing the reusable shell, interaction states, and responsive behaviour."
    >
        <x-slot:actions>
            <x-ui.button
                variant="secondary"
                icon="panel-right-open"
                x-on:click="$dispatch('open-drawer', 'activity-panel')"
            >
                Open panel
            </x-ui.button>

            <x-ui.button
                icon="plus"
                x-on:click="ExpressCloud.toast({
                    type: 'success',
                    title: 'Action completed',
                    message: 'The interface feedback system is working.',
                })"
            >
                Primary action
            </x-ui.button>
        </x-slot:actions>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Revenue today', 'value' => '₦0', 'change' => 'No sales recorded'],
                ['label' => 'Transactions', 'value' => '0', 'change' => 'Current branch'],
                ['label' => 'Low stock items', 'value' => '0', 'change' => 'No attention required'],
                ['label' => 'Active staff', 'value' => '0', 'change' => 'No active sessions'],
            ] as $metric)
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">
                        {{ $metric['label'] }}
                    </p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                        {{ $metric['value'] }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ $metric['change'] }}
                    </p>
                </x-ui.card>
            @endforeach
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <x-ui.card
                title="Interface states"
                description="Buttons, feedback, fields, and status treatments used throughout Express Cloud."
            >
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input
                        name="example-search"
                        label="Product search"
                        placeholder="Search product name or SKU"
                        help="Fields maintain a minimum 44px touch target."
                    />

                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700">
                            Record state
                        </p>
                        <div class="flex min-h-11 flex-wrap items-center gap-2">
                            <x-ui.status-badge tone="success">Active</x-ui.status-badge>
                            <x-ui.status-badge tone="warning">Pending</x-ui.status-badge>
                            <x-ui.status-badge tone="danger">Attention</x-ui.status-badge>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <x-ui.button>Save changes</x-ui.button>
                    <x-ui.button variant="secondary">Secondary</x-ui.button>
                    <x-ui.button variant="ghost">Ghost action</x-ui.button>
                    <x-ui.button variant="danger">Delete</x-ui.button>
                    <x-ui.button :loading="true" loading-text="Saving…">
                        Save
                    </x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card
                title="Loading states"
                description="Skeletons are preferred over blocking page spinners."
            >
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <x-ui.skeleton height="h-10" width="w-10" class="rounded-full" />
                        <div class="flex-1 space-y-2">
                            <x-ui.skeleton width="w-1/2" />
                            <x-ui.skeleton height="h-3" width="w-3/4" />
                        </div>
                    </div>

                    <x-ui.skeleton height="h-28" />
                    <x-ui.skeleton height="h-11" width="w-40" />
                </div>
            </x-ui.card>
        </section>

        <x-ui.drawer name="activity-panel" title="Activity details">
            <p class="text-sm leading-6 text-slate-600">
                Drawers are preferred for contextual editing and record details.
                The production activity feed is implemented with audit logs in a
                later sprint.
            </p>
        </x-ui.drawer>
    </x-layout.app-shell>
</x-layout.app>
