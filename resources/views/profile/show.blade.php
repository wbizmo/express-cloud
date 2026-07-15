<x-layout.app title="My Profile | Express Cloud">
    <x-layout.app-shell
        page-title="My profile"
        page-description="Review your assigned identity and update only your profile picture."
    >
        <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
            <x-ui.card title="Profile picture">
                <div class="flex flex-col items-center text-center">
                    @if ($account->profile_picture_path)
                        <img
                            src="{{ Storage::disk(config('authentication.profile_picture.disk'))->url($account->profile_picture_path) }}"
                            alt="{{ $account->displayName() }}"
                            class="h-32 w-32 rounded-full object-cover ring-4 ring-slate-100"
                        >
                    @else
                        <div class="grid h-32 w-32 place-items-center rounded-full bg-slate-200 text-3xl font-bold text-slate-700 ring-4 ring-slate-100">
                            {{ $account->initials() }}
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('staff.profile.picture.update') }}"
                        enctype="multipart/form-data"
                        class="mt-6 w-full space-y-3"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="file"
                            name="profile_picture"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                        >

                        <x-ui.button type="submit" variant="secondary" class="w-full">
                            Update picture
                        </x-ui.button>
                    </form>

                    @if ($account->profile_picture_path)
                        <form
                            method="POST"
                            action="{{ route('staff.profile.picture.destroy') }}"
                            class="mt-2 w-full"
                        >
                            @csrf
                            @method('DELETE')

                            <x-ui.button type="submit" variant="ghost" class="w-full">
                                Remove picture
                            </x-ui.button>
                        </form>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card
                title="Account information"
                description="These details are assigned by an administrator and cannot be changed here."
            >
                <dl class="divide-y divide-slate-200">
                    @foreach ([
                        'Full name' => $account->displayName(),
                        'Account status' => $account->status->value,
                        'Access key' => $loginKey,
                    ] as $label => $value)
                        <div class="grid gap-1 py-4 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm font-medium text-slate-500">
                                {{ $label }}
                            </dt>
                            <dd @class([
                                'text-sm font-medium text-slate-950',
                                'font-mono tracking-[0.12em]' => $label === 'Access key',
                            ])>
                                {{ $value }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">
                    Your name, access key, email, role, permissions, branch
                    assignments, and account status can only be managed by an
                    authorised administrator.
                </p>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
