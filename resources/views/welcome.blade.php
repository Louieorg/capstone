<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<link rel="icon" type="image/png" href="{{ asset('images/logolikha.png') }}">
<title>LIKHA</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
<script src="https://unpkg.com/lucide@latest"></script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="welcome-page">

<!-- Custom cursor -->
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- ══ NAV ══ -->
<nav id="navbar">
  <a href="#" class="nav-logo">
    <img src="{{ asset('images/logolikha.png') }}" alt="LIKHA" class="nav-logo-icon">
    <span class="nav-logo-text">LIKHA</span>
  </a>
  <div class="nav-links">
    <a href="#how">How it works</a>
    <a href="#features">Features</a>
    <a href="#roles">For you</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="nav-cta">
    <a href="/login" class="btn-ghost-sm">Sign in</a>
    <a href="{{ route('discover') }}" class="btn-amber">Try Browsing</a>
  </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
  <div class="hero-grid">
    <svg viewBox="0 0 1440 900" fill="none" preserveAspectRatio="xMidYMid slice">
      <defs>
        <pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse">
          <path d="M 80 0 L 0 0 0 80" fill="none" stroke="white" stroke-width="1"/>
        </pattern>
      </defs>
      <rect width="1440" height="900" fill="url(#grid)"/>
    </svg>
  </div>

  <div class="hero-split">
   <div class="hero-left">
    <div class="eyebrow">
      <span class="eyebrow-dot"></span>
      Cagayan State University - Aparri Campus
    </div>

    <h1 class="hero-h1">
      Every problem<br>
      <span class="line-amber">is an idea</span>
      <span class="line-dim"> waiting.</span>
    </h1>

    <p class="hero-sub">
      LIKHA connects your school's offices directly to students — real institutional
problems, confirmed as worth building, waiting for a capstone team to take on.
    </p>

    <div class="hero-actions">
      <a href="/register" class="btn-hero">
        <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
        Start exploring
      </a>
      <a href="#how" class="btn-hero-ghost">
        See how it works
        <i data-lucide="chevron-down" style="width:15px;height:15px;"></i>
      </a>
    </div>

    <div class="hero-stats">
      <div class="stat-item">
        <span class="stat-num">{{ $totalProblems ?? '0' }}</span>
        <span class="stat-label">Problems Reported</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">{{ $ideaCandidates ?? '0' }}</span>
        <span class="stat-label">Capstone Candidates</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">{{ $totalCategories ?? '0' }}</span>
        <span class="stat-label">Campus Categories</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">Rule-Based</span>
        <span class="stat-label">Scoring Engine</span>
      </div>
    </div>
    </div>

    {{-- RIGHT: inline auth card --}}
  <div class="auth-card">
    <div class="auth-tabs">
      <button class="auth-tab active" data-tab="login">Sign in</button>
      <button class="auth-tab" data-tab="register">Create account</button>
    </div>

    <div class="auth-body">

      {{-- LOGIN PANEL --}}
      <div class="panel active" id="panel-login">
        <form method="POST" action="{{ route('login') }}">
          @csrf
          @if ($errors->has('email'))
            <div class="auth-warning" role="alert">
              Incorrect email or password. Please try again.
            </div>
          @endif
          <div class="form-group">
            <label class="form-label">Email address</label>
            <div class="input-wrap">
              <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="you@school.edu" required>
              {{-- mail icon --}}
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">
              Password
              <a href="#">Forgot password?</a>
            </label>
            <div class="input-wrap">
              <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn-primary">Sign in to LIKHA</button>
        </form>

        <div class="divider-row"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" width="16">
          Continue with Google
        </a>
      </div>

      {{-- REGISTER PANEL --}}
      <div class="panel" id="panel-register">
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <div class="form-row">
            <div>
              <label class="form-label">First name</label>
              <input type="text" name="first_name" class="form-input" placeholder="Juan" required>
            </div>
            <div>
              <label class="form-label">Last name</label>
              <input type="text" name="last_name" class="form-input" placeholder="dela Cruz" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-input" placeholder="you@school.edu" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="regPw" class="form-input"
              placeholder="Min. 8 characters" oninput="chkStr(this.value)" required>
            <div class="strength-bar">
              <div class="s-seg" id="ss1"></div>
              <div class="s-seg" id="ss2"></div>
              <div class="s-seg" id="ss3"></div>
              <div class="s-seg" id="ss4"></div>
            </div>
            <div class="s-lbl" id="sLbl">Enter a password</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-input"
              placeholder="Re-enter password" required>
          </div>
          <button type="submit" class="btn-primary">Create account</button>
        </form>

        <div class="divider-row"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" width="16">
          Continue with Google
        </a>
        <p class="terms-note">By registering you agree to our <a href="{{ route('terms') }}">Terms</a> & <a href="{{ route('help.privacy') }}">Privacy Rights</a>.</p>
      </div>

    </div>
  </div>

  </div>
</section>

<!-- ══ MARQUEE ══ -->
<div class="marquee-strip">
  <div class="marquee-track">
    <span class="hi">▲ Submit Problems</span>
    <span>·</span>
    <span>Rule-Based Ideas</span>
    <span>·</span>
    <span class="hi">▲ Adviser Reviews</span>
    <span>·</span>
    <span>Capstone Ideas</span>
    <span>·</span>
    <span>Campus Analytics</span>
    <span>·</span>
    <span class="hi">▲ Severity Scoring</span>
    <span>·</span>
    <span>Confidence Levels</span>
    <span>·</span>
    <span>Category Insights</span>
    <span>·</span>
    <span class="hi">▲ Submit Problems</span>
    <span>·</span>
    <span>AI Idea Generation</span>
    <span>·</span>
    <span class="hi">▲ Adviser Reviews</span>
    <span>·</span>
    <span>Capstone Ideas</span>
    <span>·</span>
    <span>Campus Analytics</span>
    <span>·</span>
    <span class="hi">▲ Severity Scoring</span>
    <span>·</span>
    <span>Confidence Levels</span>
    <span>·</span>
    <span>Category Insights</span>
    <span>·</span>
  </div>
</div>

<!-- ══ HOW IT WORKS ══ -->
<div id="how">
  <div class="section">
    <div class="reveal">
      <p class="section-label">The Process</p>
      <h2 class="section-title">From complaint<br>to capstone — in three steps.</h2>
      <p class="section-sub">No more staring at a blank page wondering what to build. LIKHA turns what's broken around you into your next big project.</p>
    </div>

    <div class="steps reveal reveal-delay-1">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-icon"><i data-lucide="file-plus" style="width:20px;height:20px;"></i></div>
        <h3>Offices identify what needs solving</h3>
        <p>an office reports a real institutional problem and confirms it's suitable capstone material.</p>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-icon"><i data-lucide="cpu" style="width:20px;height:20px;"></i></div>
        <h3>Students pick it up</h3>
        <p>browse confirmed capstone ideas, see exactly which office to talk to, and start building.</p>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-icon"><i data-lucide="graduation-cap" style="width:20px;height:20px;"></i></div>
        <h3>Or, discover an emerging pattern</h3>
        <p>for problems no office has raised yet, LIKHA can still detect a recurring issue across independent reports and generate a rule-based recommendation once enough evidence accumulates.</p>
      </div>
    </div>
  </div>
</div>

<!-- ══ FEATURES ══ -->
<div id="features" class="features-wrap">
  <div style="max-width:1100px; margin:0 auto; padding:100px 48px 0;">
    <div class="reveal">
      <p class="section-label">What's Inside</p>
      <h2 class="section-title">Built for the<br>whole campus ecosystem.</h2>
    </div>
  </div>
  <div style="max-width:1100px; margin:0 auto; padding:48px;">
    <div class="features reveal reveal-delay-1">

      <div class="feature">
        <div class="feature-icon"><i data-lucide="brain" style="width:22px;height:22px;color:#fbb034;"></i></div>
        <h3>Rule-Based Idea Generation</h3>
        <p>Problems are grouped by keyword clusters and scored across four dimensions — frequency, impact, severity, and confidence — to surface the most relevant capstone ideas automatically.</p>
        <span class="feature-badge">Powered by custom scoring</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="bar-chart-2" style="width:22px;height:22px;color:#3b82f6;"></i></div>
        <h3>Category Analytics</h3>
        <p>Visual breakdowns of which campus areas are generating the most problems, who is being affected, and how frequently issues occur — giving advisers and admins real data to act on.</p>
        <span class="feature-badge" style="background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2);color:#3b82f6;">Live dashboard</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="shield-check" style="width:22px;height:22px;color:#10b981;"></i></div>
        <h3>Adviser Evaluation</h3>
        <p>Advisers review AI-generated ideas and submit their own feasibility, impact, complexity, and innovation scores. The final idea score blends both — 70% system, 30% adviser — for a balanced recommendation.</p>
        <span class="feature-badge" style="background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.2);color:#10b981;">Hybrid scoring</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="git-branch" style="width:22px;height:22px;color:#f97316;"></i></div>
        <h3>Duplicate Detection</h3>
        <p>Live keyword matching flags similar problems as you type, preventing redundant submissions and helping students upvote existing issues instead — making the community data cleaner and stronger.</p>
        <span class="feature-badge" style="background:rgba(249,115,22,.1);border-color:rgba(249,115,22,.2);color:#f97316;">Real-time</span>
      </div>

    </div>
  </div>
</div>

<!-- ══ ROLES ══ -->
<div id="roles">
  <div class="section">
    <div class="reveal">
      <p class="section-label">Who it's for</p>
      <h2 class="section-title">Everyone has a role<br>in solving campus problems.</h2>
      <p class="section-sub">LIKHA is built around three types of users, each with a clear purpose in the system.</p>
    </div>

    <div class="roles">

      <div class="role-card reveal">
        <div class="role-icon"><i data-lucide="user" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Student</p>
        <h3>Report & Discover</h3>
        <p>You see the problems every day. LIKHA gives you a way to turn those frustrations into meaningful research.</p>
        <ul class="role-list">
          <li>Submit campus problems</li>
          <li>Upvote existing issues</li>
          <li>Browse AI-generated ideas</li>
          <li>Save ideas for your capstone</li>
          <li>Track idea status</li>
        </ul>
      </div>

      <div class="role-card reveal reveal-delay-1">
        <div class="role-icon"><i data-lucide="clipboard-check" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Adviser</p>
        <h3>Guide & Validate</h3>
        <p>Review AI-generated ideas and add your academic expertise to ensure the best projects get built.</p>
        <ul class="role-list">
          <li>Review idea candidates</li>
          <li>Score feasibility & impact</li>
          <li>Leave feedback comments</li>
          <li>Recommend or revise ideas</li>
          <li>Track your review progress</li>
        </ul>
      </div>

      <div class="role-card reveal reveal-delay-2">
        <div class="role-icon"><i data-lucide="shield" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Admin</p>
        <h3>Moderate & Analyze</h3>
        <p>Keep the system healthy and use campus-wide data to identify the most critical areas for improvement.</p>
        <ul class="role-list">
          <li>Approve or reject submissions</li>
          <li>View category analytics</li>
          <li>Monitor trending problems</li>
          <li>Manage campus categories</li>
          <li>Oversee all users</li>
        </ul>
      </div>

    </div>
  </div>
</div>

<!-- ══ FAQ ══ -->
<div id="faq" class="section">
  <div class="reveal">
    <p class="section-label">Common Questions</p>
    <h2 class="section-title">Frequently asked<br>questions.</h2>
    <p class="section-sub">Find quick answers about using LIKHA, submitting feedback, and exploring recommendations.</p>
  </div>

  <div class="faq-categories reveal reveal-delay-1" role="tablist" aria-label="FAQ categories">
    <button class="faq-category active" type="button" role="tab" aria-selected="true" data-category="getting-started">Getting started</button>
    <button class="faq-category" type="button" role="tab" aria-selected="false" data-category="feedback">Feedback</button>
    <button class="faq-category" type="button" role="tab" aria-selected="false" data-category="recommendations">Recommendations</button>
    <button class="faq-category" type="button" role="tab" aria-selected="false" data-category="roles">Roles &amp; notifications</button>
    <button class="faq-category" type="button" role="tab" aria-selected="false" data-category="account">Account &amp; access</button>
  </div>

  <div class="faq-list reveal reveal-delay-1">

    <div class="faq-item" data-category="getting-started"><button class="faq-question" type="button"><span>What is LIKHA?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>LIKHA is a web-based Decision Support System that assists students in discovering capstone project ideas based on recurring institutional problems.</p></div></div>

    <div class="faq-item" data-category="getting-started"><button class="faq-question" type="button"><span>Who can use LIKHA?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Students, faculty, staff, advisers, and administrators may all use LIKHA, each according to their assigned role and permissions.</p></div></div>

    <div class="faq-item" data-category="getting-started"><button class="faq-question" type="button"><span>Do I need to install any software to use LIKHA?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>No. LIKHA is fully web-based and only requires a supported browser and an internet connection.</p></div></div>

    <div class="faq-item" data-category="getting-started"><button class="faq-question" type="button"><span>How do I create an account?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>An account may be created through the Registration page, or by using the Google Login option.</p></div></div>

    <div class="faq-item" data-category="getting-started"><button class="faq-question" type="button"><span>Is email verification required?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Yes. Newly registered accounts must be verified through the link sent to the registered email address before full access is granted.</p></div></div>

    <div class="faq-item" data-category="feedback"><button class="faq-question" type="button"><span>What happens after I submit feedback?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Submitted feedback is placed under Pending Review and evaluated by an administrator, who may approve or reject the submission.</p></div></div>

    <div class="faq-item" data-category="feedback"><button class="faq-question" type="button"><span>Why was my feedback rejected?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Feedback may be rejected if it lacks sufficient detail, supporting evidence, or does not meet the system's validity criteria.</p></div></div>

    <div class="faq-item" data-category="feedback"><button class="faq-question" type="button"><span>Can I edit feedback after submitting it?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Once submitted, feedback is generally not editable; users should ensure accuracy before submission.</p></div></div>

    <div class="faq-item" data-category="feedback"><button class="faq-question" type="button"><span>What is the purpose of supporting evidence?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Supporting evidence substantiates a feedback submission and assists the administrator in the validation process.</p></div></div>

    <div class="faq-item" data-category="feedback"><button class="faq-question" type="button"><span>What does Duplicate Detection do?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>It automatically identifies feedback entries that closely resemble existing reports, reducing redundant submissions.</p></div></div>

    <div class="faq-item" data-category="recommendations"><button class="faq-question" type="button"><span>How are recommendations generated?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Recommendations are generated by the Decision Support System through problem clustering, severity analysis, confidence analysis, and rule-based generation.</p></div></div>

    <div class="faq-item" data-category="recommendations"><button class="faq-question" type="button"><span>Does Artificial Intelligence generate the recommendations?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>No. The AI component, Ollama Llama 3.2, only enhances the wording of the title, description, and objectives. All decisions originate from the rule-based DSS.</p></div></div>

    <div class="faq-item" data-category="recommendations"><button class="faq-question" type="button"><span>How many approved feedback entries are needed to generate a recommendation?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>A minimum threshold of validated feedback entries within a problem cluster is required; the exact number is defined by the system's configuration.</p></div></div>

    <div class="faq-item" data-category="recommendations"><button class="faq-question" type="button"><span>What is the difference between Severity and Confidence?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Severity reflects how urgent or significant a problem is, while Confidence reflects how reliable the supporting data for that problem is.</p></div></div>

    <div class="faq-item" data-category="recommendations"><button class="faq-question" type="button"><span>Can I save a recommendation for later?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Yes. Users may use the Save Idea feature to bookmark recommendations of interest for future reference.</p></div></div>

    <div class="faq-item" data-category="roles"><button class="faq-question" type="button"><span>How do I know if I have a new notification?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>A notification indicator will appear on the Notifications icon whenever a relevant system event occurs.</p></div></div>

    <div class="faq-item" data-category="roles"><button class="faq-question" type="button"><span>What criteria do advisers use to evaluate recommendations?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Advisers evaluate recommendations based on feasibility, innovation, complexity, and impact.</p></div></div>

    <div class="faq-item" data-category="roles"><button class="faq-question" type="button"><span>Can advisers reject a recommendation?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Advisers provide formal ratings and remarks through the evaluation process; the recommendation's evaluation record reflects this assessment.</p></div></div>

    <div class="faq-item" data-category="account"><button class="faq-question" type="button"><span>Can I change my password?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Yes. Users may update their password through the Change Password page under Profile settings.</p></div></div>

    <div class="faq-item" data-category="account"><button class="faq-question" type="button"><span>Is Dark Mode available on all pages?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Yes. Dark Mode applies to the entire system interface once enabled.</p></div></div>

    <div class="faq-item" data-category="account"><button class="faq-question" type="button"><span>Who do I contact if I forget my password?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Users may use the Forgot Password feature to reset their password without administrator assistance.</p></div></div>

    <div class="faq-item" data-category="roles"><button class="faq-question" type="button"><span>Can an administrator delete a user account?</span><i data-lucide="chevron-down" class="faq-chevron"></i></button>
      <div class="faq-answer"><p>Yes. Administrators may manage, deactivate, or remove user accounts through the Manage Users page.</p></div></div>

  </div>
</div>

<!-- ══ CTA BAND ══ -->
<div class="cta-band reveal">
  <h2>Your campus has problems.<br><span>LIKHA has ideas.</span></h2>
  <p>Join the community turning everyday frustrations into solutions that actually get built.</p>
  <div class="cta-band-actions">
    <a href="/register" class="btn-hero">
      <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
      Create your account
    </a>
    <a href="/feedback" class="btn-hero-ghost">
      Browse problems
    </a>
  </div>
</div>

<!-- ══ FOOTER ══ -->
<footer>
  <a href="#" class="footer-logo">
    <img src="{{ asset('images/logolikha.png') }}" alt="LIKHA" class="nav-logo-icon" style="width:26px;height:26px;">
    <span>LIKHA</span>
  </a>
  <p>A campus problem-to-capstone intelligence system.</p>
  
</footer>

<script>
// ── Custom cursor ──
const cursor     = document.getElementById('cursor');
const cursorRing = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;

document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.left = mx + 'px';
  cursor.style.top  = my + 'px';
});

// Ring follows with lag
(function animRing() {
  rx += (mx - rx) * 0.12;
  ry += (my - ry) * 0.12;
  cursorRing.style.left = rx + 'px';
  cursorRing.style.top  = ry + 'px';
  requestAnimationFrame(animRing);
})();

// Expand on hover
document.querySelectorAll('a, button').forEach(el => {
  el.addEventListener('mouseenter', () => {
    cursor.style.width = '20px'; cursor.style.height = '20px';
    cursorRing.style.width = '54px'; cursorRing.style.height = '54px';
  });
  el.addEventListener('mouseleave', () => {
    cursor.style.width = '10px'; cursor.style.height = '10px';
    cursorRing.style.width = '36px'; cursorRing.style.height = '36px';
  });
});

// ── Navbar scroll ──
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

// ── Scroll reveal ──
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Lucide icons ──
lucide.createIcons();

// ── FAQ accordion ──
document.querySelectorAll('.faq-question').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.closest('.faq-item').classList.toggle('open');
  });
});

document.querySelectorAll('.faq-category').forEach(categoryButton => {
  categoryButton.addEventListener('click', () => {
    const selectedCategory = categoryButton.dataset.category;

    document.querySelectorAll('.faq-category').forEach(button => {
      const isActive = button === categoryButton;
      button.classList.toggle('active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    document.querySelectorAll('.faq-item').forEach(item => {
      const isVisible = item.dataset.category === selectedCategory;
      item.classList.toggle('is-hidden', !isVisible);
      item.classList.remove('open');
    });
  });
});

// Tab switching
document.querySelectorAll('.auth-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
  });
});

// If Laravel redirected back with errors, open the right panel
@if ($errors->has('email') && !$errors->has('name'))
  document.querySelector('[data-tab="login"]').click();
@elseif ($errors->has('name'))
  document.querySelector('[data-tab="register"]').click();
@endif

// Password strength meter
function chkStr(v) {
  const segs = ['ss1','ss2','ss3','ss4'];
  const cls  = ['w','m','g','s'];
  const lbs  = ['Too short','Weak','Getting there','Strong'];
  segs.forEach(id => document.getElementById(id).className = 's-seg');
  if (!v.length) { document.getElementById('sLbl').textContent = 'Enter a password'; return; }
  let score = 0;
  if (v.length >= 8)       score++;
  if (/[A-Z]/.test(v))     score++;
  if (/[0-9]/.test(v))     score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  for (let i = 0; i < score; i++) document.getElementById(segs[i]).classList.add(cls[i]);
  document.getElementById('sLbl').textContent = lbs[score - 1] || 'Too short';
}
</script>
</body>
</html>
