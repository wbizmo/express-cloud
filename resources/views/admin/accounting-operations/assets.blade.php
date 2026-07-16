<x-layout.app title="Fixed assets | Express Cloud">
<x-layout.app-shell page-title="Fixed assets" page-description="Operational fixed-asset register with depreciation and accounting controls.">
<div data-page-header class="mb-5"></div>
<div class="grid gap-6 xl:grid-cols-[430px_1fr]">
<x-ui.card title="Add asset">
<form method="POST" action="{{ route('admin.accounting-operations.assets.store') }}" class="space-y-4">
@csrf
<x-ui.input name="name" label="Asset name" required />
<x-ui.input name="category" label="Category" required />
<label><span class="mb-2 block text-sm font-medium">Branch</span><select name="branch_id"><option value="">No branch assigned</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
<div class="grid gap-4 sm:grid-cols-2"><x-ui.input name="acquired_at" type="date" label="Acquired date" required /><x-ui.input name="useful_life_months" type="number" label="Useful life (months)" required /></div>
<div class="grid gap-4 sm:grid-cols-2"><x-ui.input name="cost" type="number" step="0.01" label="Cost" required /><x-ui.input name="salvage_value" type="number" step="0.01" label="Salvage value" /></div>
<x-ui.input name="serial_number" label="Serial number" />
<x-ui.input name="location" label="Location" />
<label class="block"><span class="mb-2 block text-sm font-medium">Notes</span><textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 p-3"></textarea></label>
<x-ui.button type="submit">Add asset</x-ui.button>
</form>
</x-ui.card>
<x-ui.card title="Asset register">
<div class="space-y-3">@forelse($assets as $asset)<article class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap justify-between gap-3"><div><strong>{{ $asset->name }}</strong><p class="mt-1 text-sm text-slate-500">{{ $asset->asset_code }} · {{ $asset->category }}</p></div><div class="text-right"><p class="font-semibold">₦{{ number_format($asset->cost_kobo/100,2) }}</p><p class="text-sm text-slate-500">Monthly depreciation ₦{{ number_format($asset->monthlyDepreciationKobo()/100,2) }}</p><p class="mt-2 text-sm"><a href="{{ route('admin.accounting-operations.documents.pdf',['fixed_asset',$asset]) }}">PDF</a> · <a href="{{ route('admin.accounting-operations.documents.spreadsheet',['fixed_asset',$asset]) }}">Spreadsheet</a></p></div></div></article>@empty<p class="text-sm text-slate-500">No assets recorded.</p>@endforelse</div>
</x-ui.card>
</div>
</x-layout.app-shell>
</x-layout.app>
