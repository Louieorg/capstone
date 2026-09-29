<x-guest-layout>
    <div class="mb-4 text-sm" style="color: var(--text2);">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" style="color: var(--green);" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" style="color: var(--text);" />
            <x-text-input id="email" class="block mt-1 w-full lk-input" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" style="color: var(--red);" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="btn-amber" style="text-transform: none; letter-spacing: normal;">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
