<div
    x-data="quickCustomer('{{ route('admin.customers.quick-store') }}', '{{ csrf_token() }}')"
    x-on:open-customer-modal.window="open = true"
>
    {{-- Internal button removed – now opened via event --}}

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-3 sm:p-5"
        role="dialog"
        aria-modal="true"
        aria-labelledby="quick-customer-title"
    >
        <section
            x-on:click.outside="open = false"
            class="flex max-h-[min(82vh,720px)] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
                <div>
                    <h2 id="quick-customer-title" class="text-lg font-bold text-slate-950">Add customer</h2>
                    <p class="mt-1 text-sm text-slate-600">Only the customer name is required.</p>
                </div>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close customer form"
                >
                    ×
                </button>
            </header>

            <form class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4">
                    <p x-show="error" x-text="error" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700"></p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Name *</span>
                            <input x-model="form.name" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">Phone *</span>
                            <input x-model="form.phone" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">WhatsApp</span>
                            <input x-model="form.whatsapp_phone" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Email</span>
                            <input type="email" x-model="form.email" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Address</span>
                            <textarea x-model="form.address" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></textarea>
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Notes</span>
                            <textarea x-model="form.notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></textarea>
                        </label>
                        <!--<p x-show="error" x-text="error" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700"></p>-->
                    </div>
                </div>

                <footer class="sticky bottom-0 flex shrink-0 justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4">
                    <button type="button" x-on:click="open = false" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button
                        type="button"
                        x-on:click="save"
                        :disabled="saving"
                        class="min-h-10 rounded-xl bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
                        x-text="saving ? 'Saving…' : 'Save customer'"
                    ></button>
                </footer>
            </form>
        </section>
    </div>
</div>

<script>
function quickCustomer(endpoint, csrf) {
    return {
        open: false,
        saving: false,
        error: '',
        form: {name: '', phone: '', whatsapp_phone: '', email: '', address: '', notes: ''},
        async save() {
            this.saving = true;
            this.error = '';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(this.form),
                });
                const payload = await response.json();
                if (!response.ok) {
                    if (payload.errors) {
                        const messages = Object.values(payload.errors).flat().join(' ');
                        throw new Error(messages);
                    }
                    throw new Error(payload.message || 'Could not save customer.');
                }
                // Dispatch event that the parent listens to (customer-selected)
                window.dispatchEvent(new CustomEvent('customer-selected', {detail: {id: payload.id}}));
                this.open = false;
                this.form = {name: '', phone: '', whatsapp_phone: '', email: '', address: '', notes: ''};
            } catch (error) {
                this.error = error.message;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>