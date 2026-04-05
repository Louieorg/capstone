@extends('layouts.app')

@section('title', 'Home')
@section('subtitle', 'Welcome back — here\'s what\'s happening on campus.')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
@endpush

@section('content')

<style>
/* ── Tokens ── */
:root {
  --amber:      #fbb034;
  --adim:       rgba(251,176,52,0.10);
  --amid:       rgba(251,176,52,0.22);
  --aborder-h:  rgba(251,176,52,0.30);
  --green:      #5fcd8a;
  --green-bg:   rgba(95,205,138,0.1);
  --green-b:    rgba(95,205,138,0.22);
}

/* Light mode overrides for amber (more readable on white) */
:root:not(.dark) {
  --amber:      #b57318;
  --adim:       rgba(186,117,23,0.08);
  --amid:       rgba(186,117,23,0.18);
  --aborder-h:  rgba(186,117,23,0.35);
  --green:      #15803d;
  --green-bg:   rgba(22,163,74,0.08);
  --green-b:    rgba(22,163,74,0.2);
}

/* ── Animations ── */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.anim-1 { animation: fadeInUp .5s ease both; }
.anim-2 { animation: fadeInUp .5s .08s ease both; }
.anim-3 { animation: fadeInUp .5s .16s ease both; }
.anim-4 { animation: fadeInUp .5s .24s ease both; }

@keyframes pdot {
  0%,100% { opacity: 1; transform: scale(1); }
  50%     { opacity: .45; transform: scale(.75); }
}
.eyebrow-dot { animation: pdot 2s ease-in-out infinite; }

/* ── Eyebrow pill ── */
.eyebrow {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 5px 13px; border-radius: 999px;
  background: var(--adim); border: 1px solid var(--amid);
  font-size: 10.5px; font-weight: 600; letter-spacing: .1em;
  text-transform: uppercase; color: var(--amber);
  margin-bottom: 18px;
}

/* ── Greeting bar ── */
.greeting-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 0; margin-bottom: 28px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
:root:not(.dark) .greeting-bar { border-color: rgba(0,0,0,0.07); }
.greeting-name {
  font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
  color: inherit;
}
.greeting-sub { font-size: 12px; color: #7e8194; margin-top: 2px; }
:root:not(.dark) .greeting-sub { color: #8a8898; }

/* ── Hero headline ── */
.hero-h {
  font-family: 'Sora', sans-serif;
  font-size: clamp(26px, 3.5vw, 40px);
  font-weight: 800; line-height: 1.1; letter-spacing: -.02em;
  margin-bottom: 14px;
}
.hero-h .amb {
  background: linear-gradient(90deg, #fbb034, #f97316);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
:root:not(.dark) .hero-h .amb {
  background: linear-gradient(90deg, #b57318, #c2410c);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-sub {
  font-size: 14px; line-height: 1.7; max-width: 500px;
  color: #7e8194; margin-bottom: 32px;
}
:root:not(.dark) .hero-sub { color: #6b6880; }

/* ── Stat grid ── */
.stat-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 14px; margin-bottom: 36px;
}
.stat-card {
  background: #13141a; border: 1px solid rgba(255,255,255,.07);
  border-radius: 16px; padding: 22px 20px;
  position: relative; overflow: hidden;
  transition: border-color .2s, transform .2s;
}
:root:not(.dark) .stat-card {
  background: #ffffff; border-color: rgba(0,0,0,.08);
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.stat-card.featured {
  border-color: var(--amid);
}
.stat-card::after {
  content: ''; position: absolute; top: -20px; right: -20px;
  width: 70px; height: 70px; border-radius: 50%;
  background: var(--adim); pointer-events: none;
}
.stat-card:hover {
  border-color: var(--aborder-h);
  transform: translateY(-3px);
}
.stat-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px; color: var(--amber);
}
.stat-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: .08em;
  text-transform: uppercase; color: #7e8194; margin-bottom: 8px;
}
:root:not(.dark) .stat-label { color: #8a8898; }
.stat-val {
  font-family: 'Sora', sans-serif; font-size: 42px; font-weight: 800;
  line-height: 1; margin-bottom: 6px; color: var(--amber);
}
.stat-card:not(.featured) .stat-val { color: inherit; }
.stat-hint { font-size: 11.5px; color: #5e6175; }
:root:not(.dark) .stat-hint { color: #9a97b0; }

/* ── Section header ── */
.sec-head {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px; margin-top: 44px;
}
.sec-title {
  font-family: 'Sora', sans-serif; font-size: 15px;
  font-weight: 700; color: inherit;
}
.sec-link {
  font-size: 12px; font-weight: 600;
  color: var(--amber); text-decoration: none;
}

/* ── Action cards ── */
.action-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 12px; margin-bottom: 36px;
}
.action-card {
  background: #13141a; border: 1px solid rgba(255,255,255,.07);
  border-radius: 14px; padding: 20px 18px;
  display: flex; flex-direction: column; gap: 12px;
  text-decoration: none; color: inherit;
  transition: border-color .2s, transform .2s;
}
:root:not(.dark) .action-card {
  background: #ffffff; border-color: rgba(0,0,0,.08);
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.action-card:hover {
  border-color: var(--aborder-h);
  transform: translateY(-3px);
}
.action-icon {
  width: 40px; height: 40px; border-radius: 11px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  color: var(--amber);
}
.action-title {
  font-family: 'Sora', sans-serif; font-size: 13.5px;
  font-weight: 700; margin-bottom: 5px; color: inherit;
  transition: color .15s;
}
.action-card:hover .action-title { color: var(--amber); }
.action-desc { font-size: 12.5px; color: #7e8194; line-height: 1.6; }
:root:not(.dark) .action-desc { color: #6b6880; }
.action-arrow {
  margin-top: auto; align-self: flex-end; font-size: 16px;
  color: #5e6175; transition: transform .2s, color .2s;
}
.action-card:hover .action-arrow { transform: translate(3px,-3px); color: var(--amber); }

/* ── Trending rows ── */
.trend-list { display: flex; flex-direction: column; gap: 10px; }
.trend-row {
  background: #13141a; border: 1px solid rgba(255,255,255,.07);
  border-radius: 14px; padding: 16px 18px;
  display: flex; align-items: flex-start; gap: 14px;
  transition: border-color .2s, transform .2s;
}
:root:not(.dark) .trend-row {
  background: #ffffff; border-color: rgba(0,0,0,.08);
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.trend-row:hover {
  border-color: var(--aborder-h);
  transform: translateY(-2px);
}
.trend-rank {
  font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700;
  color: var(--amber); width: 22px; flex-shrink: 0; padding-top: 1px;
}
.trend-body { flex: 1; min-width: 0; }
.trend-text {
  font-size: 13.5px; line-height: 1.5; margin-bottom: 10px; color: inherit;
}
.trend-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; }
.tag-cat {
  padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 500;
  background: rgba(255,255,255,.05); color: #7e8194;
  border: 1px solid rgba(255,255,255,.07);
}
:root:not(.dark) .tag-cat {
  background: rgba(0,0,0,.05); color: #6b6880; border-color: rgba(0,0,0,.08);
}
.tag-idea {
  padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
  background: var(--green-bg); color: var(--green); border: 1px solid var(--green-b);
}
.trend-link {
  font-size: 11.5px; font-weight: 600;
  color: var(--amber); text-decoration: none; margin-left: auto;
}
.vote-chip {
  flex-shrink: 0; display: flex; align-items: center; gap: 5px;
  padding: 6px 12px; border-radius: 9px;
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07);
  font-size: 12px; font-weight: 600; color: #7e8194;
}
:root:not(.dark) .vote-chip {
  background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.08); color: #6b6880;
}
.vote-up { color: var(--amber); }

/* ── Empty state ── */
.empty-state {
  background: #13141a; border: 1px solid rgba(255,255,255,.07);
  border-radius: 14px; padding: 48px; text-align: center;
  font-size: 13.5px; color: #5e6175;
}
:root:not(.dark) .empty-state {
  background: #ffffff; border-color: rgba(0,0,0,.08); color: #9a97b0;
}

/* ── Submit button ── */
.btn-submit {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 18px; border-radius: 10px;
  background: var(--adim); border: 1px solid var(--amid);
  color: var(--amber); font-size: 13px; font-weight: 600;
  font-family: 'DM Sans', sans-serif; text-decoration: none;
  transition: background .15s; cursor: pointer;
}
.btn-submit:hover { background: var(--amid); }

/* ── Mobile ── */
@media (max-width: 640px) {
  .stat-grid    { grid-template-columns: 1fr; }
  .action-grid  { grid-template-columns: 1fr; }
}
@media (min-width: 641px) and (max-width: 900px) {
  .stat-grid    { grid-template-columns: repeat(2, 1fr); }
  .action-grid  { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="max-w-5xl mx-auto space-y-0">

  {{-- Greeting bar --}}
  <div class="greeting-bar anim-1">
    <div>
      @auth
        <div class="greeting-name">
          @php
            $hour = now()->hour;
            $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
          @endphp
          {{ $greet }}, {{ auth()->user()->first_name ?? auth()->user()->name }}
        </div>
        <div class="greeting-sub">{{ now()->format('l, F j') }} · {{ $pendingCount ?? 0 }} problems pending review</div>
      @else
        <div class="greeting-name">Welcome to LIKHA</div>
        <div class="greeting-sub">Turn campus problems into capstone ideas.</div>
      @endauth
    </div>
    <a href="{{ route('feedback.create') }}" class="btn-submit">
      <i data-lucide="plus" style="width:14px;height:14px;"></i>
      Submit Problem
    </a>
  </div>

  {{-- Hero --}}
  <div class="anim-1" style="margin-bottom:32px">
    <div class="eyebrow">
      <span class="eyebrow-dot w-2 h-2 rounded-full" style="background:var(--amber);display:inline-block"></span>
      Campus Innovation Hub
    </div>
    <h1 class="hero-h">
      Turn campus problems into<br>
      <span class="amb">capstone ideas.</span>
    </h1>
    <p class="hero-sub">
      LIKHA helps students discover meaningful capstone ideas by analyzing
      real problems reported by the campus community.
    </p>
  </div>

  {{-- Stat cards --}}
  <div class="stat-grid anim-2">

    <div class="stat-card featured">
      <div class="stat-icon">
        <i data-lucide="file-text" style="width:17px;height:17px;"></i>
      </div>
      <div class="stat-label">Problems Reported</div>
      <div class="stat-val">{{ $totalProblems ?? '—' }}</div>
      <div class="stat-hint">Submitted by campus community</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon">
        <i data-lucide="lightbulb" style="width:17px;height:17px;"></i>
      </div>
      <div class="stat-label">Capstone Candidates</div>
      <div class="stat-val">{{ $ideaCandidates ?? '—' }}</div>
      <div class="stat-hint">AI-scored & under review</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon">
        <i data-lucide="layout-grid" style="width:17px;height:17px;"></i>
      </div>
      <div class="stat-label">Campus Categories</div>
      <div class="stat-val">{{ $totalCategories ?? '—' }}</div>
      <div class="stat-hint">Across all departments</div>
    </div>

  </div>

  {{-- Quick actions --}}
  <div class="anim-3" style="margin-bottom:36px">
    <div class="sec-head">
      <div class="sec-title">Quick actions</div>
    </div>

    <div class="action-grid">

      <a href="{{ route('feedback.create') }}" class="action-card">
        <div class="action-icon">
          <i data-lucide="pencil-line" style="width:17px;height:17px;"></i>
        </div>
        <div>
          <div class="action-title">Submit a Problem</div>
          <div class="action-desc">Report a campus issue that affects students and services.</div>
        </div>
        <span class="action-arrow">↗</span>
      </a>

      <a href="{{ route('feedback.index') }}" class="action-card">
        <div class="action-icon">
          <i data-lucide="search" style="width:17px;height:17px;"></i>
        </div>
        <div>
          <div class="action-title">Browse Problems</div>
          <div class="action-desc">Explore issues across campus and upvote what matters.</div>
        </div>
        <span class="action-arrow">↗</span>
      </a>

      <a href="{{ route('feedback.summary') }}" class="action-card">
        <div class="action-icon">
          <i data-lucide="bar-chart-3" style="width:17px;height:17px;"></i>
        </div>
        <div>
          <div class="action-title">Category Insights</div>
          <div class="action-desc">See trends and AI-suggested ideas by category.</div>
        </div>
        <span class="action-arrow">↗</span>
      </a>

    </div>
  </div>

  {{-- Trending --}}
  <div class="anim-4">
    <div class="sec-head">
      <div class="sec-title">Trending campus problems</div>
      <a href="{{ route('feedback.index') }}" class="sec-link">View all →</a>
    </div>

    @if(isset($trending) && $trending->isEmpty())
      <div class="empty-state">
        No trending problems yet. Be the first to submit one!
      </div>

    @elseif(isset($trending))
      <div class="trend-list">
        @foreach($trending as $i => $problem)
          <div class="trend-row">
            <div class="trend-rank">#{{ $i + 1 }}</div>
            <div class="trend-body">
              <div class="trend-text">{{ $problem->description }}</div>
              <div class="trend-tags">
                <span class="tag-cat">{{ $problem->category }}</span>
                @if($problem->is_idea_candidate ?? false)
                  <span class="tag-idea">
                    <i data-lucide="check-circle" style="width:11px;height:11px;display:inline;margin-right:3px;vertical-align:middle;"></i>
                    Idea Candidate
                  </span>
                @endif
                <a href="{{ route('feedback.category', $problem->category) }}" class="trend-link">
                  View related →
                </a>
              </div>
            </div>
            <div class="vote-chip">
              <span class="vote-up">▲</span>
              {{ $problem->votes_count }}
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

</div>
@endsection