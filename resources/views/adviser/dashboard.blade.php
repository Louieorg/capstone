@extends('layouts.app')

@section('title', 'Adviser Dashboard')
@section('subtitle', 'Review AI-generated capstone ideas and track your evaluations')

@section('content')

<style>
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.22);
  }

  @keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .anim-1 { animation: fadeInUp .4s ease both; }
  .anim-2 { animation: fadeInUp .4s .07s ease both; }
  .anim-3 { animation: fadeInUp .4s .14s ease both; }
  .anim-4 { animation: fadeInUp .4s .21s ease both; }

  .dash-card {
    background: white; border: 1px solid #f3f4f6;
    border-radius: 18px; overflow: hidden;
    transition: box-shadow .2s, border-color .2s;
  }
  .dark .dash-card { background: #1e293b; border-color: rgba(255,255,255,.06); }

  .dash-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #f3f4f6;
  }
  .dark .dash-card-header { border-color: rgba(255,255,255,.06); }
  .dash-card-title { font-family:'Sora',sans-serif; font-size:.9rem; font-weight:600; }

  /* ── Stat card ── */
  .stat-card { position:relative; overflow:hidden; border-radius:16px; padding:20px 22px; border:1px solid #f3f4f6; background:white; }
  .dark .stat-card { background:#1e293b; border-color:rgba(255,255,255,.06); }
  .stat-card::after { content:''; position:absolute; top:-20px; right:-20px; width:70px; height:70px; border-radius:50%; background:var(--amber-dim); pointer-events:none; }

  /* ── Category row ── */
  .cat-row {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; border-radius:12px;
    border:1px solid #f3f4f6; transition: all .18s;
    text-decoration:none;
  }
  .dark .cat-row { border-color:rgba(255,255,255,.06); }
  .cat-row:hover { border-color:var(--amber-border); background:var(--amber-dim); transform:translateX(3px); }

  /* ── Review row ── */
  .review-row {
    padding:14px 16px; border-radius:12px;
    border:1px solid #f3f4f6; transition:border-color .18s;
  }
  .dark .review-row { border-color:rgba(255,255,255,.06); }
  .review-row:hover { border-color:var(--amber-border); }

  /* ── Recommendation pill ── */
  .pill { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.7rem; font-weight:600; }
  .pill-green  { background:rgba(22,163,74,.1);  color:#16a34a; border:1px solid rgba(22,163,74,.2); }
  .pill-amber  { background:var(--amber-dim);    color:var(--amber); border:1px solid var(--amber-border); }
  .pill-red    { background:rgba(239,68,68,.1);  color:#ef4444; border:1px solid rgba(239,68,68,.2); }
  .pill-blue   { background:rgba(59,130,246,.1); color:#3b82f6; border:1px solid rgba(59,130,246,.2); }

  /* ── Empty state ── */
  .empty-state { padding:36px 20px; text-align:center; }
  .empty-state p { font-size:.8125rem; color:#9ca3af; margin-top:8px; }

  /* ── Progress bar ── */
  .prog-bar { height:6px; border-radius:999px; background:#f3f4f6; overflow:hidden; }
  .dark .prog-bar { background:rgba(255,255,255,.06); }
  .prog-fill { height:100%; border-radius:999px; background:linear-gradient(to right,#fbb034,#f97316); transition:width .6s ease; }
</style>

<div class="max-w-6xl mx-auto space-y-6">

  {{-- ══ STAT CARDS ══ --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 anim-1">

    {{-- Total ready categories --}}
    <div class="stat-card">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3 border" style="background:var(--amber-dim); border-color:var(--amber-border);">
        <i data-lucide="layers" class="w-4 h-4" style="color:var(--amber);"></i>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Ready to Review</p>
      <p class="text-3xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $readyCategories->count() }}</p>
      <p class="text-xs text-gray-400 mt-1">categories with ideas</p>
    </div>

    {{-- Pending --}}
    <div class="stat-card">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3 border border-red-100 dark:border-red-900/30" style="background:rgba(239,68,68,.08);">
        <i data-lucide="clock" class="w-4 h-4 text-red-500"></i>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Awaiting Review</p>
      <p class="text-3xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $pendingCategories->count() }}</p>
      <p class="text-xs text-gray-400 mt-1">not yet reviewed</p>
    </div>

    {{-- Reviewed --}}
    <div class="stat-card">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3 border border-green-100 dark:border-green-900/30" style="background:rgba(22,163,74,.08);">
        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Reviewed</p>
      <p class="text-3xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $reviewedCategories->count() }}</p>
      <p class="text-xs text-gray-400 mt-1">categories done</p>
    </div>

    {{-- Review progress --}}
    <div class="stat-card">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3 border border-blue-100 dark:border-blue-900/30" style="background:rgba(59,130,246,.08);">
        <i data-lucide="trending-up" class="w-4 h-4 text-blue-500"></i>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Completion</p>
      @php $pct = $readyCategories->count() > 0 ? round(($reviewedCategories->count() / $readyCategories->count()) * 100) : 0; @endphp
      <p class="text-3xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $pct }}%</p>
      <div class="prog-bar mt-2"><div class="prog-fill" style="width:{{ $pct }}%"></div></div>
    </div>

  </div>

  {{-- ══ MAIN GRID ══ --}}
  <div class="grid md:grid-cols-3 gap-5">

    {{-- ── LEFT: Pending + Reviewed categories (spans 2) ── --}}
    <div class="md:col-span-2 space-y-5">

      {{-- Pending review --}}
      <div class="dash-card anim-2">
        <div class="dash-card-header">
          <span class="dash-card-title text-gray-800 dark:text-gray-100">⏳ Needs Your Review</span>
          @if($pendingCategories->count())
          <span class="pill pill-red">{{ $pendingCategories->count() }} pending</span>
          @endif
        </div>
        <div class="p-4 space-y-2">
          @forelse($pendingCategories as $cat)
          <a href="{{ route('feedback.category', $cat->category) }}" class="cat-row group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border" style="background:var(--amber-dim); border-color:var(--amber-border);">
              <i data-lucide="folder-open" class="w-4 h-4" style="color:var(--amber);"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-800 dark:text-white truncate group-hover:text-amber-500 transition" style="font-family:'Sora',sans-serif;">
                {{ $cat->category }}
              </p>
              <p class="text-xs text-gray-400">{{ $cat->total_reports }} {{ Str::plural('report', $cat->total_reports) }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="pill pill-red">Unreviewed</span>
              <span class="text-gray-300 dark:text-gray-600 group-hover:text-amber-400 transition">→</span>
            </div>
          </a>
          @empty
          <div class="empty-state">
            <p class="text-2xl">✅</p>
            <p>All categories reviewed — great work!</p>
          </div>
          @endforelse
        </div>
      </div>

      {{-- Reviewed categories --}}
      @if($reviewedCategories->count())
      <div class="dash-card anim-3">
        <div class="dash-card-header">
          <span class="dash-card-title text-gray-800 dark:text-gray-100">✅ Already Reviewed</span>
          <span class="pill pill-green">{{ $reviewedCategories->count() }} done</span>
        </div>
        <div class="p-4 space-y-2">
          @foreach($reviewedCategories as $cat)
          @php $eval = $evaluations[$cat->category] ?? null; @endphp
          <a href="{{ route('feedback.category', $cat->category) }}" class="cat-row group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-green-100 dark:border-green-900/30" style="background:rgba(22,163,74,.08);">
              <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-800 dark:text-white truncate group-hover:text-amber-500 transition" style="font-family:'Sora',sans-serif;">
                {{ $cat->category }}
              </p>
              <p class="text-xs text-gray-400">{{ $cat->total_reports }} {{ Str::plural('report', $cat->total_reports) }}
                @if($eval) · Score: <span class="font-medium" style="color:var(--amber);">{{ $eval->final_score ?? $eval->overall_score }}</span>@endif
              </p>
            </div>
            <span class="pill pill-green shrink-0">Reviewed</span>
          </a>
          @endforeach
        </div>
      </div>
      @endif

      {{-- Recent review activity --}}
      <div class="dash-card anim-4">
        <div class="dash-card-header">
          <span class="dash-card-title text-gray-800 dark:text-gray-100">🕐 Recent Reviews</span>
          <span class="text-xs text-gray-400">Latest activity</span>
        </div>
        <div class="p-4 space-y-3">
          @forelse($recentReviews as $review)
          <div class="review-row">
            <div class="flex items-start justify-between gap-3 mb-1">
              <p class="text-sm font-semibold text-gray-800 dark:text-white leading-snug" style="font-family:'Sora',sans-serif;">
                {{ $review->idea_title }}
              </p>
              <span class="pill shrink-0
                @if($review->recommendation === 'Recommended') pill-green
                @elseif($review->recommendation === 'Needs Revision') pill-amber
                @else pill-red @endif">
                {{ $review->recommendation }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-2">{{ $review->comment }}</p>
            <div class="flex items-center justify-between text-xs text-gray-400">
              <span class="pill pill-blue">{{ $review->category }}</span>
              <span>{{ $review->created_at->diffForHumans() }}</span>
            </div>
          </div>
          @empty
          <div class="empty-state">
            <p class="text-2xl">📋</p>
            <p>No reviews submitted yet. Start by reviewing a category above.</p>
          </div>
          @endforelse
        </div>
      </div>

    </div>

    {{-- ── RIGHT: Recommendation summary ── --}}
    <div class="space-y-5">

      {{-- Recommendation breakdown --}}
      <div class="dash-card anim-2">
        <div class="dash-card-header">
          <span class="dash-card-title text-gray-800 dark:text-gray-100">📊 My Recommendations</span>
        </div>
        <div class="p-5 space-y-4">

          @if($totalReviewed > 0)

          @foreach([
            'Recommended'     => ['pill-green', 'check-circle'],
            'Needs Revision'  => ['pill-amber', 'edit'],
            'Not Recommended' => ['pill-red',   'x-circle'],
          ] as $label => [$pillClass, $icon])
          @php $count = $recommendationStats[$label] ?? 0; $pct = $totalReviewed > 0 ? round(($count / $totalReviewed) * 100) : 0; @endphp
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <div class="flex items-center gap-2">
                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5
                  {{ $label === 'Recommended' ? 'text-green-500' : ($label === 'Needs Revision' ? 'text-amber-500' : 'text-red-500') }}"></i>
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $label }}</span>
              </div>
              <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $count }}</span>
            </div>
            <div class="prog-bar">
              <div class="prog-fill" style="width:{{ $pct }}%;
                background:{{ $label === 'Recommended' ? 'linear-gradient(to right,#16a34a,#22c55e)' : ($label === 'Needs Revision' ? 'linear-gradient(to right,#fbb034,#f97316)' : 'linear-gradient(to right,#ef4444,#f87171)') }}">
              </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $pct }}%</p>
          </div>
          @endforeach

          <div class="border-t border-gray-100 dark:border-slate-700 pt-4 mt-2">
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-400">Total reviews</span>
              <span class="text-sm font-bold" style="color:var(--amber); font-family:'Sora',sans-serif;">{{ $totalReviewed }}</span>
            </div>
          </div>

          @else
          <div class="empty-state">
            <p class="text-2xl">📝</p>
            <p>Your review summary will appear here once you start evaluating ideas.</p>
          </div>
          @endif

        </div>
      </div>

      {{-- Quick tip card --}}
      <div class="rounded-2xl p-5 border" style="background:var(--amber-dim); border-color:var(--amber-border);">
        <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--amber);">💡 How to review</p>
        <ol class="text-xs text-gray-600 dark:text-gray-400 space-y-2 leading-relaxed">
          <li class="flex gap-2"><span class="font-bold shrink-0" style="color:var(--amber);">1.</span> Click a pending category above</li>
          <li class="flex gap-2"><span class="font-bold shrink-0" style="color:var(--amber);">2.</span> Read the AI-generated top idea and its explanation</li>
          <li class="flex gap-2"><span class="font-bold shrink-0" style="color:var(--amber);">3.</span> Click <strong>Add Review</strong> and fill in your scores</li>
          <li class="flex gap-2"><span class="font-bold shrink-0" style="color:var(--amber);">4.</span> Your score is combined with the system score for a final rating</li>
        </ol>
      </div>

      {{-- Overall progress --}}
      <div class="dash-card">
        <div class="dash-card-header">
          <span class="dash-card-title text-gray-800 dark:text-gray-100">Overall Progress</span>
        </div>
        <div class="p-5">
          @php $total = $readyCategories->count(); $done = $reviewedCategories->count(); @endphp
          <div class="flex items-end justify-between mb-3">
            <div>
              <p class="text-3xl font-bold" style="font-family:'Sora',sans-serif; color:var(--amber);">{{ $done }}/{{ $total }}</p>
              <p class="text-xs text-gray-400">categories reviewed</p>
            </div>
            @if($total > 0 && $done === $total)
            <span class="pill pill-green">Complete!</span>
            @elseif($pendingCategories->count() > 0)
            <span class="pill pill-red">{{ $pendingCategories->count() }} left</span>
            @endif
          </div>
          <div class="prog-bar" style="height:8px;">
            <div class="prog-fill" style="width:{{ $total > 0 ? round(($done/$total)*100) : 0 }}%"></div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

@endsection