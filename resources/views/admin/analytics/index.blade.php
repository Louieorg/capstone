@extends('layouts.admin')

@section('title', 'System Analytics')
@section('subtitle', 'Deeper trends behind the dashboard summary')

@section('content')

<style>
.an-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 900px) { .an-grid { grid-template-columns: 1fr; } }

.an-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden;
}
.an-head { padding: 14px 18px; border-bottom: 1px solid var(--border); }
.an-title { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
.an-sub { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.an-body { padding: 18px; }

.an-stat-row { display: flex; gap: 12px; flex-wrap: wrap; }
.an-stat {
  flex: 1; min-width: 120px; background: var(--surface2); border: 1px solid var(--border);
  border-radius: 12px; padding: 14px;
}
.an-stat-label { font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted2); margin-bottom: 6px; }
.an-stat-val { font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800; color: var(--text); }
.an-stat.approved .an-stat-val { color: var(--green); }
.an-stat.pending .an-stat-val { color: var(--amber); }
.an-stat.rejected .an-stat-val { color: var(--red); }
.an-stat.flagged .an-stat-val { color: var(--red); }

.empty-state { padding: 32px; text-align: center; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ STATUS BREAKDOWN ══ --}}
  <div class="an-stat-row">
    <div class="an-stat approved">
      <div class="an-stat-label">Approved</div>
      <div class="an-stat-val">{{ $statusBreakdown['approved'] }}</div>
    </div>
    <div class="an-stat pending">
      <div class="an-stat-label">Pending</div>
      <div class="an-stat-val">{{ $statusBreakdown['pending'] }}</div>
    </div>
    <div class="an-stat rejected">
      <div class="an-stat-label">Rejected</div>
      <div class="an-stat-val">{{ $statusBreakdown['rejected'] }}</div>
    </div>
    <div class="an-stat flagged">
      <div class="an-stat-label">Flagged</div>
      <div class="an-stat-val">{{ $statusBreakdown['flagged'] }}</div>
    </div>
  </div>

  {{-- ══ MONTHLY TREND (previously computed, never displayed) ══ --}}
  <div class="an-card">
    <div class="an-head">
      <div class="an-title">Reports over time</div>
      <div class="an-sub">Monthly submission volume</div>
    </div>
    <div class="an-body">
      @if($monthlyReports->isEmpty())
        <div class="empty-state"><p class="empty-text">Not enough data yet.</p></div>
      @else
        <canvas id="monthlyChart" height="80"></canvas>
      @endif
    </div>
  </div>

  <div class="an-grid">

    {{-- ══ CATEGORY BREAKDOWN ══ --}}
    <div class="an-card">
      <div class="an-head">
        <div class="an-title">Reports per category</div>
        <div class="an-sub">Approved, unflagged feedback</div>
      </div>
      <div class="an-body">
        @if($categoryData->isEmpty())
          <div class="empty-state"><p class="empty-text">No approved feedback yet.</p></div>
        @else
          <canvas id="categoryChart" height="180"></canvas>
        @endif
      </div>
    </div>

    {{-- ══ AFFECTED GROUPS ══ --}}
    <div class="an-card">
      <div class="an-head">
        <div class="an-title">Who is affected</div>
        <div class="an-sub">Distribution across reported groups</div>
      </div>
      <div class="an-body">
        @if($affectedGroupData->isEmpty())
          <div class="empty-state"><p class="empty-text">No data yet.</p></div>
        @else
          <canvas id="affectedChart" height="180"></canvas>
        @endif
      </div>
    </div>

    {{-- ══ IMPACT LEVELS (previously computed, never displayed) ══ --}}
    <div class="an-card">
      <div class="an-head">
        <div class="an-title">Reported impact size</div>
        <div class="an-sub">How many people each report says are affected</div>
      </div>
      <div class="an-body">
        @if($impactLevels->isEmpty())
          <div class="empty-state"><p class="empty-text">No data yet.</p></div>
        @else
          <canvas id="impactChart" height="180"></canvas>
        @endif
      </div>
    </div>

    {{-- ══ FREQUENCY BREAKDOWN ══ --}}
    <div class="an-card">
      <div class="an-head">
        <div class="an-title">Reported frequency</div>
        <div class="an-sub">How often reporters say the problem occurs</div>
      </div>
      <div class="an-body">
        @if($frequencyBreakdown->isEmpty())
          <div class="empty-state"><p class="empty-text">No data yet.</p></div>
        @else
          <canvas id="frequencyChart" height="180"></canvas>
        @endif
      </div>
    </div>

  </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const isDark  = document.documentElement.classList.contains('dark');
  const gridClr = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
  const tickClr = isDark ? '#3e4055' : '#b0b8c1';
  const tipBg   = isDark ? '#13141a' : '#111014';
  const amber   = isDark ? '#fbb034' : '#b57318';
  const palette = ['#fbb034', '#60a5fa', '#5fcd8a', '#f87171', '#a78bfa', '#f472b6'];

  const baseOpts = {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { backgroundColor: tipBg, titleColor: '#f0f0f5', bodyColor: '#9a9bb0', padding: 10, cornerRadius: 8 }
    },
  };

  @if($monthlyReports->isNotEmpty())
  new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
      labels: {!! json_encode($monthlyReports->pluck('label')) !!},
      datasets: [{
        data: {!! json_encode($monthlyReports->pluck('total')) !!},
        borderColor: amber, backgroundColor: 'rgba(251,176,52,0.12)',
        fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: amber,
      }]
    },
    options: {
      ...baseOpts,
      scales: {
        x: { grid: { display: false }, ticks: { color: tickClr, font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: gridClr }, ticks: { color: tickClr, font: { size: 11 }, stepSize: 1 } }
      }
    }
  });
  @endif

  @if($categoryData->isNotEmpty())
  new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
      labels: {!! json_encode($categoryData->pluck('category')) !!},
      datasets: [{ data: {!! json_encode($categoryData->pluck('total')) !!}, backgroundColor: amber, borderRadius: 6 }]
    },
    options: {
      ...baseOpts,
      scales: {
        x: { grid: { display: false }, ticks: { color: tickClr, font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: gridClr }, ticks: { color: tickClr, font: { size: 11 }, stepSize: 1 } }
      }
    }
  });
  @endif

  @if($affectedGroupData->isNotEmpty())
  new Chart(document.getElementById('affectedChart'), {
    type: 'doughnut',
    data: {
      labels: {!! json_encode($affectedGroupData->pluck('affected_group')) !!},
      datasets: [{ data: {!! json_encode($affectedGroupData->pluck('total')) !!}, backgroundColor: palette, borderWidth: 0 }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: tickClr, font: { size: 11 }, padding: 12 } },
        tooltip: baseOpts.plugins.tooltip,
      }
    }
  });
  @endif

  @if($impactLevels->isNotEmpty())
  new Chart(document.getElementById('impactChart'), {
    type: 'bar',
    data: {
      labels: {!! json_encode($impactLevels->pluck('affected_users')) !!},
      datasets: [{ data: {!! json_encode($impactLevels->pluck('total')) !!}, backgroundColor: '#60a5fa', borderRadius: 6 }]
    },
    options: {
      indexAxis: 'y',
      ...baseOpts,
      scales: {
        x: { beginAtZero: true, grid: { color: gridClr }, ticks: { color: tickClr, font: { size: 11 }, stepSize: 1 } },
        y: { grid: { display: false }, ticks: { color: tickClr, font: { size: 11 } } }
      }
    }
  });
  @endif

  @if($frequencyBreakdown->isNotEmpty())
  new Chart(document.getElementById('frequencyChart'), {
    type: 'polarArea',
    data: {
      labels: {!! json_encode($frequencyBreakdown->pluck('frequency')) !!},
      datasets: [{ data: {!! json_encode($frequencyBreakdown->pluck('total')) !!}, backgroundColor: palette.map(c => c + 'cc') }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: tickClr, font: { size: 11 }, padding: 12 } },
        tooltip: baseOpts.plugins.tooltip,
      },
      scales: { r: { grid: { color: gridClr }, ticks: { display: false } } }
    }
  });
  @endif
});
</script>
@endpush

@endsection