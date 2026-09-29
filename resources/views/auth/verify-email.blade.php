<x-guest-layout>
    <div class="mb-4 text-sm" style="color: var(--text2);">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4" style="color: var(--green);">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--amber);">
                    <path d="M22 11t3 0 0 1 0 0a11 11 0 11-6.9-6.9C15.3 4.2 16 3 18 3h4z"></path>
                    <polyline points="9 12l2 2 4-4"></polyline>
                </svg>
                <span style="font-family: 'Sora', sans-serif; font-weight: 600; font-size: 14px; color: var(--text);">{{ __('Verification email sent!') }}</span>
            </div>
            <p class="mt-1" style="font-size: 12.5px; line-height: 1.6; color: var(--text2);">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="btn-amber" style="text-transform: none; letter-spacing: normal;">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn-ghost">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
