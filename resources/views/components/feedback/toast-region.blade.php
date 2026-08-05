<div
    x-data
    class="pointer-events-none fixed inset-x-4 bottom-4 z-[100] flex flex-col gap-3 sm:inset-x-auto sm:bottom-auto sm:right-5 sm:top-5 sm:w-[380px]"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <article
            x-cloak
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-3 sm:translate-y-0"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="translate-y-2 opacity-0 sm:translate-x-3 sm:translate-y-0"
            class="pointer-events-auto flex items-start gap-3 rounded-[10px] border border-slate-200 bg-white p-4 shadow-[var(--ec-shadow-elevated)]"
        >
            <div
                class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg"
                :class="{
                    'bg-emerald-50 text-emerald-700': toast.type === 'success',
                    'bg-red-50 text-red-700': toast.type === 'error',
                    'bg-amber-50 text-amber-700': toast.type === 'warning',
                    'bg-blue-50 text-blue-700': toast.type === 'info',
                }"
            >
                <span
                    class="material-symbols-outlined text-[18px] leading-none"
                    aria-hidden="true"
                    x-text="{
                        success: 'check_circle',
                        error: 'cancel',
                        warning: 'warning',
                        info: 'info',
                    }[toast.type] ?? 'info'"
                ></span>
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-950" x-text="toast.title"></p>
                <p
                    x-show="toast.message"
                    class="mt-1 text-sm leading-5 text-slate-500"
                    x-text="toast.message"
                ></p>
            </div>

            <button
                type="button"
                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                aria-label="Dismiss notification"
                @click="$store.toasts.remove(toast.id)"
            >
                <x-ui.icon name="x" :size="16" />
            </button>
        </article>
    </template>
</div>
