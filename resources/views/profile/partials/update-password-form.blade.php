<section>
    <header>
        <h3 class="font-['Sora'] text-base font-bold leading-snug text-slate-900 dark:text-white">
            {{ __('Update Password') }}
        </h3>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" style="color: var(--text);" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full lk-input" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" style="color: var(--red);" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" style="color: var(--text);" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full lk-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" style="color: var(--red);" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" style="color: var(--text);" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full lk-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" style="color: var(--red);" />
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4 dark:border-white/10">
            <button type="submit" class="btn-amber">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium"
                    style="color: var(--green);"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
