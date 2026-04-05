@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="lk-wrap">

  <div class="orb1"></div>
  <div class="orb2"></div>
  <div class="grain"></div>
  <div class="divider-v"></div>

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
      <div class="eyebrow"><span class="edot"></span>Join the movement</div>
      <h2 class="left-h">
        Your campus has<br>
        problems. You have<br>
        <span class="amb">the solution.</span>
      </h2>
      <p class="left-sub">
        Create an account and start turning real campus frustrations
        into validated capstone ideas — in minutes.
      </p>

      <div class="steps-list">
        <div class="step-row">
          <div class="step-num">1</div>
          <div class="step-text">
            <strong>Submit a campus problem</strong>
            <span>Takes under 2 minutes — no expertise needed</span>
          </div>
        </div>
        <div class="step-row">
          <div class="step-num">2</div>
          <div class="step-text">
            <strong>AI finds the pattern</strong>
            <span>Problems are clustered and scored automatically</span>
          </div>
        </div>
        <div class="step-row">
          <div class="step-num">3</div>
          <div class="step-text">
            <strong>Build what matters</strong>
            <span>Browse validated capstone ideas ready for your team</span>
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
            Free Account
          </div>
          <h2 class="card-h">Create your account</h2>
          <p class="card-sub">Start turning campus problems into capstone ideas</p>
        </div>

        @if ($errors->any())
          <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}">
          @csrf

          {{-- Split name row --}}
          <div class="form-row">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">First name</label>
              <div class="input-wrap">
                <input type="text" name="first_name" value="{{ old('first_name') }}" required
                  class="form-input" placeholder="Juan">
                <span class="input-icon">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Last name</label>
              <div class="input-wrap">
                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                  class="form-input" placeholder="dela Cruz">
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-top:14px">
            <label class="form-label">Email address</label>
            <div class="input-wrap">
              <input type="email" name="email" value="{{ old('email') }}" required
                class="form-input" placeholder="you@school.edu">
              <span class="input-icon">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
              <input type="password" name="password" id="pwInput" required
                class="form-input" placeholder="Min. 8 characters"
                oninput="checkStrength(this.value)">
              <span class="input-icon" style="cursor:pointer" onclick="togglePw()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </span>
            </div>
            <div class="strength-bar">
              <div class="strength-seg" id="s1"></div>
              <div class="strength-seg" id="s2"></div>
              <div class="strength-seg" id="s3"></div>
              <div class="strength-seg" id="s4"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Enter a password</div>
          </div>

          <div class="form-group">
            <label class="form-label">Confirm password</label>
            <div class="input-wrap">
              <input type="password" name="password_confirmation" required
                class="form-input" placeholder="Re-enter password">
              <span class="input-icon">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
            </div>
          </div>

          <button type="submit" class="btn-primary">Create account</button>
        </form>

        <div class="divider-row"><span>OR CONTINUE WITH</span></div>

        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" width="18" height="18" alt="Google">
          Continue with Google
        </a>

        
      </div>

      <p class="card-foot">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
      </p>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function checkStrength(val) {
  const segs = ['s1','s2','s3','s4'];
  const labels = ['Too short', 'Weak', 'Getting there', 'Strong'];
  const classes = ['s1','s2','s3','s4'];
  segs.forEach(id => { document.getElementById(id).className = 'strength-seg'; });
  if (!val.length) { document.getElementById('strengthLabel').textContent = 'Enter a password'; return; }
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  for (let i = 0; i < score; i++) document.getElementById(segs[i]).classList.add(classes[i]);
  document.getElementById('strengthLabel').textContent = labels[score - 1] || 'Too short';
}
function togglePw() {
  const inp = document.getElementById('pwInput');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
@endpush