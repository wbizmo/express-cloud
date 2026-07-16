<x-layout.app title="Document branding | Express Cloud">
<x-layout.app-shell page-title="Document branding" page-description="The logo is optional. Documents render cleanly when no logo is uploaded.">
<div data-page-header class="mb-5"></div>
<x-ui.card title="Company document identity">
<form method="POST" action="{{ route('admin.accounting-operations.branding.update') }}" enctype="multipart/form-data" class="space-y-5">
@csrf
@method('PATCH')
<x-ui.input name="business_name" label="Business name" :value="old('business_name',$branding?->business_name ?? config('app.name'))" required />
<div class="grid gap-4 md:grid-cols-3">
<x-ui.input name="address" label="Address" :value="old('address',$branding?->address)" />
<x-ui.input name="phone" label="Phone" :value="old('phone',$branding?->phone)" />
<x-ui.input name="email" type="email" label="Email" :value="old('email',$branding?->email)" />
</div>
<label class="block"><span class="mb-2 block text-sm font-medium">Company logo</span><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp" class="block w-full rounded-lg border border-slate-300 p-3"></label>
@if ($branding?->logo_path)
<input type="checkbox" name="remove_logo" value="1" data-label="Remove current logo">
@endif
<label class="block"><span class="mb-2 block text-sm font-medium">Receipt footer</span><textarea name="receipt_footer" rows="3" class="w-full rounded-lg border border-slate-300 p-3">{{ old('receipt_footer',$branding?->receipt_footer) }}</textarea></label>
<label class="block"><span class="mb-2 block text-sm font-medium">Document terms</span><textarea name="document_terms" rows="5" class="w-full rounded-lg border border-slate-300 p-3">{{ old('document_terms',$branding?->document_terms) }}</textarea></label>
<x-ui.button type="submit">Save document branding</x-ui.button>
</form>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
