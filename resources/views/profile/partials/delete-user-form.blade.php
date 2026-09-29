<section>
    <header>
        <h3 class="font-['Sora'] text-base font-bold leading-snug text-slate-900 dark:text-white">
            {{ __('Delete Account') }}
        </h3>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <div class="mt-4">
        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="btn-reject"
        >{{ __('Delete Account') }}</button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6" style="background: var(--surface);">
            @csrf
            @method('delete')

            <h3 class="font-['Sora'] text-base font-bold text-slate-900 dark:text-white">
                {{ __('Are you sure you want to delete your account?') }}
            </h3>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-5">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full lk-input"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" style="color: var(--red);" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="btn-ghost">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" class="btn-reject">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
