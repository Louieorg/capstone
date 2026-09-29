<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" style="color: var(--text);" />
            <x-text-input id="email" class="block mt-1 w-full lk-input" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" style="color: var(--red);" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" style="color: var(--text);" />
            <x-text-input id="password" class="block mt-1 w-full lk-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" style="color: var(--red);" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" style="color: var(--text);" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full lk-input"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" style="color: var(--red);" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="btn-amber" style="text-transform: none; letter-spacing: normal;">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
