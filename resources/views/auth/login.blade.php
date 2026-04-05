@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="lk-wrap">

  {{-- Ambient orbs --}}
  <div class="orb1"></div>
  <div class="orb2"></div>
  <div class="grain"></div>
  <div class="divider-v"></div>

  {{-- Grid background --}}
  <div class="grid-bg">
    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="g" width="80" height="80" patternUnits="userSpaceOnUse">
          <path d="M80 0L0 0 0 80" fill="none" stroke="white" stroke-width="1"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#g)"/>
    </svg>
  </div>

  {{-- LEFT PANEL --}}
  <div class="lk-left">
    <a href="{{ route('home') }}" class="logo">
      <div class="logo-icon">L</div>
      <span class="logo-text">LIKHA</span>
    </a>

    <div class="left-body">
      <div class="eyebrow">
        <span class="edot"></span>
        Campus Innovation System
      </div>
      <h2 class="left-h">
        Welcome back.<br>
        Keep <span class="amb">building</span><br>
        what matters.
      </h2>
      <p class="left-sub">
        Your campus problems are waiting to become capstone ideas.
        Pick up where you left off.
      </p>

      <div class="feature-list">
        <div class="feat">
          <div class="feat-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fbb034" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>
            </svg>
          </div>
          <div class="feat-text">
            <strong>AI-Scored Capstone Ideas</strong>
            <span>Ranked by frequency, impact & severity</span>
          </div>
        </div>
        <div class="feat">
          <div class="feat-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fbb034" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
          </div>
          <div class="feat-text">
            <strong>Category Analytics</strong>
            <span>See where campus pain points cluster</span>
          </div>
        </div>
        <div class="feat">
          <div class="feat-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fbb034" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="feat-text">
            <strong>Adviser Hybrid Scoring</strong>
            <span>70% system + 30% expert review</span>
          </div>
        </div>
      </div>
    </div>

    <div class="left-foot">
      <span>© {{ date('Y') }} LIKHA</span>
      <span>·</span>
      <a href="#">Privacy</a>
      <span>·</span>
      <a href="#">Terms</a>
    </div>
  </div>

  {{-- RIGHT PANEL --}}
  <div class="lk-right">
    <div class="right-inner">
      <div class="card">
        <div class="card-top">
          <div class="card-tag">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="#fbb034"><circle cx="12" cy="12" r="10"/></svg>
            Secure Login
          </div>
          <h2 class="card-h">Sign in to LIKHA</h2>
          <p class="card-sub">Turn campus frustrations into real solutions</p>
        </div>

        @if ($errors->any())
          <div class="alert-error">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="form-group">
            <label class="form-label">Email address</label>
            <div class="input-wrap">
              <input type="email" name="email" value="{{ old('email') }}" required
                class="form-input" placeholder="you@school.edu">
              <span class="input-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              Password
              <a href="#">Forgot password?</a>
            </label>
            <div class="input-wrap">
              <input type="password" name="password" required
                class="form-input" placeholder="••••••••">
              <span class="input-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
            </div>
          </div>

          <button type="submit" class="btn-primary">
            Login to your account
          </button>
        </form>

        <div class="divider-row"><span>OR CONTINUE WITH</span></div>

        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" class="g-logo" alt="Google">
          Continue with Google
        </a>
      </div>

      <p class="card-foot">
        Don't have an account?
        <a href="{{ route('register') }}">Register for free</a>
      </p>
    </div>
  </div>

</div>
@endsection