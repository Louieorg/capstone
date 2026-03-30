@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('subtitle', 'System overview and feedback moderation')

@section('content')

<style>
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.22);
  }

  @keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .anim-1 { animation: fadeInUp .45s ease both; }
  .anim-2 { animation: fadeInUp .45s .08s ease both; }
  .anim-3 { animation: fadeInUp .45s .16s ease both; }
  .anim-4 { animation: fadeInUp .45s .24s ease both; }
  .anim-5 { animation: fadeInUp .45s .32s ease both; }
  .anim-6 { animation: fadeInUp .45s .40s ease both; }
  .anim-7 { animation: fadeInUp .45s .48s ease both; }

  /* ── Stat card orb ── */
  .stat-card { position:relative; overflow:hidden; }
  .stat-card::after {
    content:''; position:absolute; top:-20px; right:-20px;
    width:70px; height:70px; border-radius:50%;
    background: var(--amber-dim); pointer-events:none;
  }

  /* ── Section card ── */
  .dash-card {
    background: white; border:1px solid #f3f4f6;
    border-radius: 18px; overflow:hidden;
    transition: box-shadow .2s, border-color .2s;
  }
  .dark .dash-card { background:#1e293b; border-color:rgba(255,255,255,.06); }
  .dash-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.09); }
  .dark .dash-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.35); }

  .dash-card-header {
    display:flex; align-items:center; justify-content:space-between;
    padding: 18px 22px; border-bottom:1px solid #f3f4f6;
  }
  .dark .dash-card-header { border-color:rgba(255,255,255,.06); }
  .dash-card-title { font-family:'Sora',sans-serif; font-size:.9375rem; font-weight:600; }
  .dash-card-body { padding: 20px 22px; }

  /* ── Row items ── */
  .row-item {
    display:flex; align-items:center; gap:12px;
    padding: 12px 14px; border-radius:12px; border:1px solid #f3f4f6;
    transition: border-color .18s, background .18s;
  }
  .dark .row-item { border-color:rgba(255,255,255,.06); }
  .row-item:hover { border-color: var(--amber-border); background: var(--amber-dim); }

  /* ── Approve / Reject buttons ── */
  .btn-approve {
    padding:7px 16px; border-radius:9px; font-size:.75rem; font-weight:600;
    background:#16a34a; color:white; border:none; cursor:pointer;
    transition: background .18s, transform .15s;
  }
  .btn-approve:hover { background:#15803d; transform:translateY(-1px); }
  .btn-reject {
    padding:7px 16px; border-radius:9px; font-size:.75rem; font-weight:600;
    background:transparent; color:#ef4444; border:1px solid #fca5a5; cursor:pointer;
    transition: all .18s;
  }
  .btn-reject:hover { background:#ef4444; color:white; transform:translateY(-1px); }

  /* ── Badge ── */
  .badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.7rem; font-weight:600; }
</style>

<div class="max-w-7xl mx-auto space-y-7">

  {{-- ══ STAT CARDS ══ --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 anim-1">

    <div class="stat-card dash-card p-6 flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Total Reports</p>
        <p class="text-4xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $totalFeedback }}</p>
        <p class="text-xs text-gray-400 mt-1">Submitted by campus</p>
      </div>
      <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border" style="background:var(--amber-dim); border-color:var(--amber-border);">
        <svg class="w-5 h-5" style="color:var(--amber);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.8L3 20l1.1-3.3A7.93 7.93 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
      </div>
    </div>

    <div class="stat-card dash-card p-6 flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Total Categories</p>
        <p class="text-4xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">{{ $totalCategories }}</p>
        <p class="text-xs text-gray-400 mt-1">Across all departments</p>
      </div>
      <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40" style="background:rgba(59,130,246,.08);">
        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/>
        </svg>
      </div>
    </div>

    <div class="stat-card dash-card p-6 flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Top Category</p>
        <p class="text-xl font-bold mt-1" style="font-family:'Sora',sans-serif; color:var(--amber);">{{ $topCategory->category ?? 'N/A' }}</p>
        <p class="text-xs text-gray-400 mt-1">Most reported area</p>
      </div>
      <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border border-green-100 dark:border-green-900/40" style="background:rgba(16,185,129,.08);">
        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- ══ CHARTS ROW ══ --}}
  <div class="grid md:grid-cols-3 gap-5 anim-2">

    {{-- Bar chart – spans 2 cols --}}
    <div class="dash-card md:col-span-2">
      <div class="dash-card-header">
        <span class="dash-card-title text-gray-800 dark:text-gray-100">Reports Per Category</span>
        <span class="text-xs text-gray-400">System Analytics</span>
      </div>
      <div class="dash-card-body">
        <canvas id="categoryChart" height="90"></canvas>
      </div>
    </div>

    {{-- Pie chart --}}
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title text-gray-800 dark:text-gray-100">Who Is Affected</span>
      </div>
      <div class="dash-card-body flex items-center justify-center" style="min-height:220px;">
        <canvas id="affectedChart"></canvas>
      </div>
    </div>

  </div>

  {{-- ══ TRENDING + OPPORTUNITIES ══ --}}
  <div class="grid md:grid-cols-2 gap-5 anim-3">

    {{-- Trending Problems --}}
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title text-gray-800 dark:text-gray-100">🔥 Trending Problems</span>
        <span class="badge" style="background:var(--amber-dim); color:var(--amber); border:1px solid var(--amber-border);">{{ $topProblems->count() }}</span>
      </div>
      <div class="dash-card-body space-y-2">
        @forelse($topProblems as $i => $problem)
        <div class="row-item">
          <span class="text-xs font-bold w-5 shrink-0" style="color:var(--amber); font-family:'Sora',sans-serif;">#{{ $i+1 }}</span>
          <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ $problem->title }}</span>
          <span class="text-xs font-medium shrink-0 px-2 py-1 rounded-lg" style="background:var(--amber-dim); color:var(--amber);">▲ {{ $problem->votes_count }}</span>
        </div>
        @empty
        <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No trending problems yet.</p>
        @endforelse
      </div>
    </div>

    {{-- Capstone Opportunities --}}
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title text-gray-800 dark:text-gray-100">💡 Capstone Opportunities</span>
        <span class="badge" style="background:rgba(16,185,129,.1); color:#16a34a; border:1px solid rgba(16,185,129,.2);">{{ $ideaCandidates->count() }}</span>
      </div>
      <div class="dash-card-body space-y-2">
        @forelse($ideaCandidates as $category)
        <div class="row-item">
          <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $category->category }}</span>
          <span class="badge shrink-0" style="background:rgba(16,185,129,.1); color:#16a34a; border:1px solid rgba(16,185,129,.2);">{{ $category->total }} reports</span>
        </div>
        @empty
        <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No categories with enough reports yet.</p>
        @endforelse
      </div>
    </div>

  </div>

  {{-- ══ PENDING APPROVALS ══ --}}
  <div class="dash-card anim-4">
    <div class="dash-card-header">
      <div class="flex items-center gap-3">
        <span class="dash-card-title text-gray-800 dark:text-gray-100">Pending Approvals</span>
        @if($pendingFeedback->count())
        <span class="badge bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">
          {{ $pendingFeedback->count() }} pending
        </span>
        @endif
      </div>
    </div>
    <div class="dash-card-body space-y-4">
      @forelse($pendingFeedback as $item)
      <div class="rounded-xl border border-gray-100 dark:border-slate-700 p-5 hover:border-amber-200 dark:hover:border-amber-800/40 transition-all">
        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2">{{ $item->description }}</p>
        <div class="flex items-center justify-between flex-wrap gap-3">
          <span class="badge" style="background:var(--amber-dim); color:var(--amber); border:1px solid var(--amber-border);">{{ $item->category }}</span>
          <div class="flex gap-2">
            <form method="POST" action="{{ route('feedback.approve', $item->id) }}">
              @csrf @method('PATCH')
              <button class="btn-approve">Approve</button>
            </form>
            <form method="POST" action="{{ route('feedback.reject', $item->id) }}">
              @csrf @method('PATCH')
              <button class="btn-reject">Reject</button>
            </form>
          </div>
        </div>
      </div>
      @empty
      <div class="py-10 text-center">
        <p class="text-2xl mb-2">✅</p>
        <p class="text-sm text-gray-400 dark:text-gray-500">All caught up — no pending feedback.</p>
      </div>
      @endforelse
    </div>
  </div>

  {{-- ══ RECENT SUBMISSIONS ══ --}}
  <div class="dash-card anim-5">
    <div class="dash-card-header">
      <span class="dash-card-title text-gray-800 dark:text-gray-100">Recent Submissions</span>
      <span class="text-xs text-gray-400">Latest activity</span>
    </div>
    <div class="dash-card-body space-y-3">
      @forelse($recentFeedback as $feedback)
      <div class="row-item">
        <div class="flex-1 min-w-0">
          <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $feedback->description }}</p>
        </div>
        <span class="badge shrink-0" style="background:var(--amber-dim); color:var(--amber); border:1px solid var(--amber-border);">{{ $feedback->category }}</span>
        <span class="text-xs text-gray-400 shrink-0">{{ $feedback->created_at->diffForHumans() }}</span>
      </div>
      @empty
      <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No recent submissions.</p>
      @endforelse
    </div>
  </div>

</div>

{{-- ══ CHART SCRIPTS ══ --}}
@php
  $labels        = $categoryData->pluck('category');
  $counts        = $categoryData->pluck('total');
  $affectedLabels = $affectedGroupData->pluck('affected_group');
  $affectedCounts = $affectedGroupData->pluck('total');
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  const isDark = document.documentElement.classList.contains('dark');
  const gridColor  = isDark ? 'rgba(255,255,255,0.05)' : '#f3f4f6';
  const tickColor  = isDark ? '#6b7280' : '#9ca3af';
  const tooltipBg  = isDark ? '#0f172a' : '#111827';

  // ── Bar chart ──
  const barCtx = document.getElementById('categoryChart');
  if (barCtx && {{ $labels->count() }} > 0) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: {!! json_encode($labels) !!},
        datasets: [{
          label: 'Reports',
          data: {!! json_encode($counts) !!},
          backgroundColor: 'rgba(251,176,52,0.85)',
          hoverBackgroundColor: '#f97316',
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        animation: { duration: 1200, easing: 'easeOutQuart' },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: tooltipBg, titleColor: '#fff', bodyColor: '#d1d5db',
            padding: 12, displayColors: false, cornerRadius: 10,
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: tickColor } },
          y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } }
        }
      }
    });
  }

  // ── Pie chart ──
  const pieCtx = document.getElementById('affectedChart');
  if (pieCtx && {{ $affectedLabels->count() }} > 0) {
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: {!! json_encode($affectedLabels) !!},
        datasets: [{
          data: {!! json_encode($affectedCounts) !!},
          backgroundColor: ['#fbb034','#3b82f6','#10b981','#ef4444','#8b5cf6'],
          hoverOffset: 6,
          borderWidth: 2,
          borderColor: isDark ? '#1e293b' : '#fff',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '60%',
        plugins: {
          legend: { position: 'bottom', labels: { color: tickColor, padding: 14, font: { size: 11 } } },
          tooltip: {
            backgroundColor: tooltipBg, titleColor: '#fff', bodyColor: '#d1d5db',
            padding: 12, cornerRadius: 10,
          }
        }
      }
    });
  }

});
</script>

@endsection