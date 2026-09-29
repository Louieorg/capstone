<section>
    <header>
        <h3 class="font-['Sora'] text-base font-bold leading-snug text-slate-900 dark:text-white">
            {{ __('Profile Information') }}
        </h3>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __("Update your account's name and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" style="color: var(--text);" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full lk-input" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" style="color: var(--red);" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" style="color: var(--text);" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full lk-input" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" style="color: var(--red);" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/5">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="rounded-md text-sm font-semibold underline focus:outline-none" style="color: var(--amber);">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium" style="color: var(--green);">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4 dark:border-white/10">
            <button type="submit" class="btn-amber">{{ __('Save') }}</button>

            @if (session('status') === 'profile-updated')
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
