@extends('layouts.app')
 
@section('content')
 
{{-- Google Fonts --}}
@push('head')
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&display=swap" rel="stylesheet"/>
@endpush
 
<style>
  /* ── Design Tokens ── */
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.25);
    --amber-glow: rgba(251,176,52,0.30);
  }
 
  /* ── Fade-up entry ── */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .anim-1 { animation: fadeInUp .5s ease both; }
  .anim-2 { animation: fadeInUp .5s .1s ease both; }
  .anim-3 { animation: fadeInUp .5s .2s ease both; }
  .anim-4 { animation: fadeInUp .5s .3s ease both; }
 
  /* ── Live eyebrow dot ── */
  @keyframes pulse-dot {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: .45; transform: scale(.75); }
  }
  .eyebrow-dot { animation: pulse-dot 2s ease-in-out infinite; }
 
  /* ── Stat card orb ── */
  .stat-card { position: relative; overflow: hidden; }
  .stat-card::after {
    content: '';
    position: absolute; top: -24px; right: -24px;
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--amber-dim);
    pointer-events: none;
  }
 
  /* ── Action card arrow ── */
  .action-card .arrow { transition: transform .2s, color .2s; color: #9ca3af; }
  .action-card:hover .arrow { transform: translate(3px,-3px); color: var(--amber); }
 
  /* ── Amber border on hover ── */
  .amber-hover { transition: border-color .2s, transform .2s, box-shadow .2s; }
  .amber-hover:hover {
    border-color: var(--amber-border) !important;
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,.18);
  }
</style>
 
<div class="max-w-6xl mx-auto px-4 py-10 space-y-14">
 
  {{-- ── HERO ── --}}
  <div class="anim-1">
    {{-- Eyebrow --}}
    <div class="inline-flex items-center gap-2 mb-5 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest border"
         style="background:var(--amber-dim); border-color:var(--amber-border); color:var(--amber);">
      <span class="eyebrow-dot w-2 h-2 rounded-full inline-block" style="background:var(--amber);"></span>
      Campus Innovation Hub
    </div>
 
    <h1 class="font-bold text-gray-800 dark:text-white leading-tight mb-4"
        style="font-family:'Sora',sans-serif; font-size:clamp(28px,4vw,44px);">
      Turn Campus Problems Into<br>
      <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
        Capstone Ideas
      </span>
    </h1>
 
    <p class="text-gray-500 dark:text-gray-400 max-w-xl leading-relaxed">
      LIKHA helps students discover meaningful capstone ideas by analyzing real
      problems reported by the campus community.
    </p>
  </div>
 
  {{-- ── STAT CARDS ── --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 anim-2">
 
    {{-- Featured --}}
    <div class="stat-card amber-hover rounded-2xl p-6 border"
         style="background:linear-gradient(135deg,rgba(251,176,52,.06) 0%,transparent 70%);
                border-color:var(--amber-border);"
         class="dark:bg-slate-800">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4 text-lg border"
           style="background:var(--amber-dim); border-color:var(--amber-border);">📋</div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Total Problems Reported</p>
      <p class="text-5xl font-bold leading-none mb-2" style="font-family:'Sora',sans-serif; color:var(--amber);">
        {{ $totalProblems ?? '—' }}
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500">Submitted by campus community</p>
    </div>
 
    <div class="stat-card amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-6">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4 text-lg border"
           style="background:var(--amber-dim); border-color:var(--amber-border);">💡</div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Capstone Idea Candidates</p>
      <p class="text-5xl font-bold leading-none mb-2 text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">
        {{ $ideaCandidates ?? '—' }}
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500">Under review</p>
    </div>
 
    <div class="stat-card amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-6">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4 text-lg border"
           style="background:var(--amber-dim); border-color:var(--amber-border);">🗂</div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Campus Categories</p>
      <p class="text-5xl font-bold leading-none mb-2 text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">
        {{ $totalCategories ?? '—' }}
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500">Across all departments</p>
    </div>
 
  </div>
 
  {{-- ── QUICK ACTIONS ── --}}
  <div class="anim-3">
    <h2 class="font-semibold text-gray-800 dark:text-white mb-5" style="font-family:'Sora',sans-serif; font-size:1.1rem; letter-spacing:.01em;">
      Quick Actions
    </h2>
 
    <div class="grid md:grid-cols-3 gap-4">
 
      <a href="{{ route('feedback.create') }}"
         class="action-card amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-6 flex flex-col gap-3 group">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl border"
             style="background:var(--amber-dim); border-color:var(--amber-border);">📝</div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-white mb-1 group-hover:text-amber-400 transition" style="font-family:'Sora',sans-serif; font-size:.95rem;">
            Submit a Problem
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
            Report a campus issue that affects students and services.
          </p>
        </div>
        <span class="arrow mt-auto self-end text-lg">↗</span>
      </a>
 
      <a href="{{ route('feedback.index') }}"
         class="action-card amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-6 flex flex-col gap-3 group">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl border"
             style="background:var(--amber-dim); border-color:var(--amber-border);">🔍</div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-white mb-1 group-hover:text-amber-400 transition" style="font-family:'Sora',sans-serif; font-size:.95rem;">
            Browse Problems
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
            Explore problems reported across campus and upvote what matters.
          </p>
        </div>
        <span class="arrow mt-auto self-end text-lg">↗</span>
      </a>
 
      <a href="{{ route('feedback.summary') }}"
         class="action-card amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-6 flex flex-col gap-3 group">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl border"
             style="background:var(--amber-dim); border-color:var(--amber-border);">📊</div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-white mb-1 group-hover:text-amber-400 transition" style="font-family:'Sora',sans-serif; font-size:.95rem;">
            Category Insights
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
            See trends and suggested capstone ideas by category.
          </p>
        </div>
        <span class="arrow mt-auto self-end text-lg">↗</span>
      </a>
 
    </div>
  </div>
 
  {{-- ── TRENDING PROBLEMS ── --}}
  <div class="anim-4">
    <div class="flex items-center justify-between mb-5">
      <h2 class="font-semibold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif; font-size:1.1rem;">
        Trending Campus Problems
      </h2>
      <a href="{{ route('feedback.index') }}" class="text-xs font-semibold transition" style="color:var(--amber);">
        View all →
      </a>
    </div>
 
    @if(isset($trending) && $trending->isEmpty())
      <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-10 text-center text-gray-400 dark:text-gray-500">
        No trending problems yet. Be the first to submit one!
      </div>
 
    @elseif(isset($trending))
      <div class="flex flex-col gap-3">
        @foreach($trending as $i => $problem)
          <div class="amber-hover bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl p-5 flex items-start gap-4">
 
            {{-- Rank --}}
            <span class="text-sm font-bold pt-0.5 w-6 shrink-0" style="font-family:'Sora',sans-serif; color:var(--amber);">
              #{{ $i + 1 }}
            </span>
 
            {{-- Body --}}
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 leading-snug">
                {{ $problem->description }}
              </p>
              <div class="flex flex-wrap gap-2 items-center">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-slate-600">
                  {{ $problem->category }}
                </span>
                @if($problem->is_idea_candidate ?? false)
                  <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border"
                        style="background:rgba(95,205,138,.1); color:#5fcd8a; border-color:rgba(95,205,138,.25);">
                    💡 Idea Candidate
                  </span>
                @endif
                <a href="{{ route('feedback.category', $problem->category) }}"
                   class="text-xs font-medium transition ml-auto" style="color:var(--amber);">
                  View related →
                </a>
              </div>
            </div>
 
            {{-- Votes --}}
            <div class="shrink-0 flex items-center gap-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-slate-700 border border-gray-100 dark:border-slate-600 transition-colors">
              ▲ {{ $problem->votes_count }}
            </div>
 
          </div>
        @endforeach
      </div>
    @endif
  </div>
 
</div>
 
@endsection