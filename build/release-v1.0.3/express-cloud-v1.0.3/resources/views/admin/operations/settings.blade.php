<x-layout.app title="Business settings | Express Cloud">
    <x-layout.app-shell
        page-title="Business settings"
        page-description="Non-secret operating settings. SMTP credentials remain in environment configuration."
    >
        <x-ui.card title="Operating settings">
            <form method="POST" action="{{ route('admin.operations.settings.update') }}" enctype="multipart/form-data" class="grid gap-6 md:grid-cols-[repeat(2,minmax(0,1fr))]">
                @csrf
                @method('PATCH')
                <x-ui.input name="business_name" label="Business name" :value="$settings->business_name" required />
                <x-ui.input name="end_of_day_digest_time" type="time" label="Digest send time" :value="substr($settings->end_of_day_digest_time, 0, 5)" required />
                <x-ui.input name="session_inactivity_minutes" type="number" label="Session inactivity minutes" :value="$settings->session_inactivity_minutes" required />
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Business logo</span>
                    <input type="file" name="business_logo" accept=".png,.jpg,.jpeg,.webp" class="block min-h-11 w-full rounded-lg border border-slate-300 p-3 text-sm">
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Head-office address</span>
                    <textarea name="head_office_address" required class="min-h-28 w-full rounded-lg border border-slate-300 p-3 text-sm">{{ $settings->head_office_address }}</textarea>
                </label>
                <div class="md:col-span-2">
                    <x-ui.button type="submit">Save settings</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
