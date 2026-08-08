<div x-show="loginOpen"
     @keydown.escape.window="loginOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-backdrop" x-cloak>

  <div @click.outside="loginOpen = false" class="modal-card"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100">

    <button @click="loginOpen = false" class="modal-close">
      <i data-lucide="x" style="width:14px;height:14px;"></i>
    </button>

    <div class="modal-body">
      {{-- Logo --}}
      <div style="text-align:center;margin-bottom:24px">
        <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:8px">
          <div class="h-logo-icon">L</div>
          <span style="font-family:'Sora',sans-serif;font-weight:700;font-size:18px;letter-spacing:.06em;color:var(--text)">LIKHA</span>
        </div>
        <div style="font-size:12.5px;color:var(--muted)">Sign in to your account</div>
      </div>

      <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:12px">
        @csrf
        <input type="email" name="email" class="modal-input" placeholder="Email address"
          x-ref="loginEmail"
          x-init="$watch('loginOpen', v => v && $nextTick(() => $refs.loginEmail.focus()))"
          required>
        <input type="password" name="password" class="modal-input" placeholder="Password" required>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px">
          <label style="display:flex;align-items:center;gap:6px;color:var(--muted);cursor:pointer">
            <input type="checkbox" name="remember" style="accent-color:var(--amber)"> Remember me
          </label>
          <a href="{{ route('password.request') }}" style="color:var(--amber);text-decoration:none;font-size:12px">Forgot password?</a>
        </div>

        <button type="submit" class="modal-btn">Sign in</button>
      </form>

      <div style="display:flex;align-items:center;gap:12px;margin:16px 0">
        <div style="flex:1;height:1px;background:var(--border)"></div>
        <span style="font-size:11px;color:var(--muted2)">OR</span>
        <div style="flex:1;height:1px;background:var(--border)"></div>
      </div>

      <a href="{{ route('google.login') }}" class="modal-google">
        <img src="https://developers.google.com/identity/images/g-logo.png" style="width:16px;height:16px;">
        Continue with Google
      </a>

      <p style="text-align:center;font-size:12px;color:var(--muted2);margin-top:18px">
        Don't have an account?
        <a href="{{ route('register') }}" style="color:var(--amber);text-decoration:none;font-weight:500">Register</a>
      </p>
    </div>
  </div>
</div>