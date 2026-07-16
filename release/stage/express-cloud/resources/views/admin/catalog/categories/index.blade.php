<x-layout.app title="Product categories | Express Cloud">
    <x-layout.app-shell
        page-title="Product categories"
        page-description="Maintain a clean classification list used by the product catalogue."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <x-ui.card title="Category directory">
                <div class="space-y-3">
                    @forelse ($records as $record)
                        <article class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 p-4">
                            <div>
                                <h2 class="font-semibold text-slate-950">{{ $record->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $record->description }}</p>
                            </div>
                            <span class="text-xs text-slate-500">{{ $record->products_count }} products</span>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No categories configured.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create category">
                <form method="POST" action="{{ route('admin.catalog.categories.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="name" label="Name" required />
                    <x-ui.input name="slug" label="Slug" required />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" class="min-h-28 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm"></textarea>
                    </label>
                    <x-ui.button type="submit" class="w-full">Create category</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
