@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('subtitle', 'System overview and feedback moderation')

@section('content')

<style>
html.dark {
  --surface:  #13141a;
  --surface2: #1a1b23;
  --border:   rgba(255,255,255,0.07);
  --border-h: rgba(251,176,52,0.28);
  --text:     #f0f0f5;
  --text2:    #9a9bb0;
  --text3:    #5e6175;
  --amber:    #fbb034;
  --adim:     rgba(251,176,52,0.10);
  --amid:     rgba(251,176,52,0.22);
  --green:    #5fcd8a;
  --green-bg: rgba(95,205,138,0.10);
  --green-b:  rgba(95,205,138,0.22);
  --red:      #f87171;
  --red-bg:   rgba(248,113,113,0.10);
  --red-b:    rgba(248,113,113,0.22);
  --blue:     #60a5fa;
  --blue-bg:  rgba(96,165,250,0.10);
  --blue-b:   rgba(96,165,250,0.20);
}
html:not(.dark) {
  --surface:  #ffffff;
  --surface2: #f9f8f6;
  --border:   rgba(0,0,0,0.08);
  --border-h: rgba(186,117,23,0.35);
  --text:     #111014;
  --text2:    #5a5870;
  --text3:    #9a97b0;
  --amber:    #b57318;
  --adim:     rgba(186,117,23,0.08);
  --amid:     rgba(186,117,23,0.18);
  --green:    #15803d;
  --green-bg: rgba(22,163,74,0.08);
  --green-b:  rgba(22,163,74,0.20);
  --red:      #dc2626;
  --red-bg:   rgba(220,38,38,0.08);
  --red-b:    rgba(220,38,38,0.20);
  --blue:     #1d4ed8;
  --blue-bg:  rgba(29,78,216,0.08);
  --blue-b:   rgba(29,78,216,0.18);
}

@keyframes fadeInUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.anim-1 { animation: fadeInUp .45s ease both; }
.anim-2 { animation: fadeInUp .45s .08s ease both; }
.anim-3 { animation: fadeInUp .45s .16s ease both; }
.anim-4 { animation: fadeInUp .45s .24s ease both; }
.anim-5 { animation: fadeInUp .45s .32s ease both; }

/* ── Stat grid ── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
@media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 500px) { .stat-grid { grid-template-columns: 1fr; } }

.s-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 20px;
  position: relative; overflow: hidden;
  transition: border-color .2s, transform .2s;
}
.s-card:hover { border-color: var(--border-h); transform: translateY(-2px); }
.s-card.featured { border-color: var(--amid); }
.s-card::after {
  content: ''; position: absolute; top: -18px; right: -18px;
  width: 64px; height: 64px; border-radius: 50%;
  background: var(--adim); pointer-events: none;
}
.s-icon {
  width: 34px; height: 34px; border-radius: 9px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 14px; color: var(--amber);
}
.s-icon.blue  { background: var(--blue-bg);  border-color: var(--blue-b);  color: var(--blue); }
.s-icon.green { background: var(--green-bg); border-color: var(--green-b); color: var(--green); }
.s-icon.red   { background: var(--red-bg);   border-color: var(--red-b);   color: var(--red); }
.s-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: .08em;
  text-transform: uppercase; color: var(--text3); margin-bottom: 7px;
}
.s-val {
  font-family: 'Sora', sans-serif; font-size: 34px; font-weight: 800;
  line-height: 1; color: var(--amber); margin-bottom: 4px;
}
.s-card:not(.featured) .s-val { color: var(--text); }
.s-hint { font-size: 11.5px; color: var(--text3); }

/* ── Dash card ── */
.dash-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden;
  transition: border-color .2s;
}
.dash-card:hover { border-color: var(--border-h); }
.dc-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px; border-bottom: 1px solid var(--border);
}
.dc-title {
  font-family: 'Sora', sans-serif; font-size: 14px;
  font-weight: 700; color: var(--text);
}
.dc-sub { font-size: 11.5px; color: var(--text3); }
.dc-body { padding: 16px 18px; }

/* ── Charts row ── */
.charts-row {
  display: grid; grid-template-columns: 1fr 300px; gap: 14px;
}
@media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }

/* ── Two col ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 768px) { .two-col { grid-template-columns: 1fr; } }

/* ── Badge ── */
.lk-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--adim);     color: var(--amber); border-color: var(--amid); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }

/* ── Row items ── */
.row-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px;
  border: 1px solid var(--border);
  transition: border-color .15s, background .15s;
  margin-bottom: 6px;
}
.row-item:last-child { margin-bottom: 0; }
.row-item:hover { border-color: var(--amid); background: var(--adim); }
.ri-rank {
  font-family: 'Sora', sans-serif; font-size: 12px;
  font-weight: 700; color: var(--amber); width: 20px; flex-shrink: 0;
}
.ri-text { flex: 1; font-size: 13px; color: var(--text2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ri-vote {
  font-size: 11.5px; font-weight: 600; color: var(--amber);
  padding: 2px 9px; border-radius: 7px;
  background: var(--adim); border: 1px solid var(--amid); flex-shrink: 0;
}

/* ── Pending items ── */
.pending-item {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 12px; padding: 16px 18px;
  margin-bottom: 10px;
  transition: border-color .2s;
}
.pending-item:last-child { margin-bottom: 0; }
.pending-item:hover { border-color: var(--amid); }
.pending-desc {
  font-size: 13.5px; color: var(--text);
  line-height: 1.65; margin-bottom: 12px;
}
.pending-foot {
  display: flex; align-items: center;
  justify-content: space-between; flex-wrap: wrap; gap: 10px;
}

/* ── Approve / Reject ── */
.btn-approve {
  padding: 7px 16px; border-radius: 9px;
  font-size: 12px; font-weight: 600;
  background: var(--green-bg); color: var(--green);
  border: 1px solid var(--green-b); cursor: pointer;
  font-family: 'DM Sans', sans-serif; transition: all .15s;
}
.btn-approve:hover { filter: brightness(1.1); transform: translateY(-1px); }
.btn-reject {
  padding: 7px 16px; border-radius: 9px;
  font-size: 12px; font-weight: 600;
  background: transparent; color: var(--red);
  border: 1px solid var(--red-b); cursor: pointer;
  font-family: 'DM Sans', sans-serif; transition: all .15s;
}
.btn-reject:hover { background: var(--red-bg); transform: translateY(-1px); }

/* ── Recent items ── */
.recent-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 14px; border-radius: 10px;
  border: 1px solid var(--border); margin-bottom: 6px;
  transition: border-color .15s;
}
.recent-item:last-child { margin-bottom: 0; }
.recent-item:hover { border-color: var(--amid); }
.ri-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--amber); flex-shrink: 0;
}
.ri-body { flex: 1; min-width: 0; }
.ri-bdesc {
  font-size: 13px; color: var(--text2);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ri-time { font-size: 11px; color: var(--text3); flex-shrink: 0; }

/* ── Bar chart bars ── */
.chart-bars {
  display: flex; align-items: flex-end; gap: 10px;
  height: 180px; padding-bottom: 0;
}
.chart-bar-wrap {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; gap: 6px; height: 100%;
}
.chart-bar-track {
  flex: 1; width: 100%; background: var(--adim);
  border-radius: 6px; border: 1px solid var(--amid);
  display: flex; align-items: flex-end; padding: 3px;
  overflow: hidden;
}
.chart-bar-fill {
  width: 100%; background: var(--amber);
  border-radius: 4px; transition: height .6s ease;
}
.chart-bar-lbl { font-size: 10px; color: var(--text3); text-align: center; }

/* ── Affected bars ── */
.affected-row { display: flex; flex-direction: column; gap: 14px; }
.aff-item { display: flex; flex-direction: column; gap: 5px; }
.aff-meta {
  display: flex; justify-content: space-between;
  font-size: 12px;
}
.aff-label { color: var(--text2); }
.aff-pct { font-weight: 600; }
.aff-track {
  height: 5px; background: var(--surface2);
  border-radius: 999px; overflow: hidden;
  border: 1px solid var(--border);
}
.aff-fill { height: 100%; border-radius: 999px; }

/* ── Empty state ── */
.empty-state { padding: 32px; text-align: center; }
.empty-ico {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 10px; color: var(--amber);
}
.empty-text { font-size: 13px; color: var(--text3); }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:18px">

  {{-- ══ STAT CARDS ══ --}}
  <div class="stat-grid anim-1">

    <div class="s-card featured">
      <div class="s-icon">
        <i data-lucide="message-square" style="width:16px;height:16px;"></i>
      </div>
      <div class="s-label">Total Reports</div>
      <div class="s-val">{{ $totalFeedback }}</div>
      <div class="s-hint">Submitted by campus</div>
    </div>

    <div class="s-card">
      <div class="s-icon blue">
        <i data-lucide="layout-grid" style="width:16px;height:16px;"></i>
      </div>
      <div class="s-label">Categories</div>
      <div class="s-val">{{ $totalCategories }}</div>
      <div class="s-hint">Across all departments</div>
    </div>

    <div class="s-card">
      <div class="s-icon green">
        <i data-lucide="lightbulb" style="width:16px;height:16px;"></i>
      </div>
      <div class="s-label">Idea Candidates</div>
      <div class="s-val">{{ $ideaCandidates->count() }}</div>
      <div class="s-hint">AI-scored problems</div>
    </div>

    <div class="s-card">
      <div class="s-icon red">
        <i data-lucide="clock" style="width:16px;height:16px;"></i>
      </div>
      <div class="s-label">Pending Review</div>
      <div class="s-val">{{ $pendingFeedback->count() }}</div>
      <div class="s-hint">Awaiting moderation</div>
    </div>

  </div>

  {{-- ══ CHARTS ROW ══ --}}
  <div class="charts-row anim-2">

    {{-- Bar chart --}}
    <div class="dash-card">
      <div class="dc-head">
        <span class="dc-title">Reports per category</span>
        <span class="dc-sub">System analytics</span>
      </div>
      <div class="dc-body">
        <div class="chart-bars">
          @foreach($categoryData as $cat)
          @php $pct = $categoryData->max('total') > 0 ? ($cat->total / $categoryData->max('total')) * 100 : 0; @endphp
          <div class="chart-bar-wrap">
            <div class="chart-bar-track">
              <div class="chart-bar-fill" style="height:{{ $pct }}%"></div>
            </div>
            <span class="chart-bar-lbl">{{ Str::limit($cat->category, 10) }}</span>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Affected groups --}}
    <div class="dash-card">
      <div class="dc-head">
        <span class="dc-title">Who is affected</span>
      </div>
      <div class="dc-body">
        <div class="affected-row">
          @php
            $total = $affectedGroupData->sum('total');
            $colors = ['var(--amber)', 'var(--blue)', 'var(--green)', 'var(--red)', 'var(--text3)'];
          @endphp
          @foreach($affectedGroupData as $i => $group)
          @php $pct = $total > 0 ? round(($group->total / $total) * 100) : 0; @endphp
          <div class="aff-item">
            <div class="aff-meta">
              <span class="aff-label">{{ $group->affected_group }}</span>
              <span class="aff-pct" style="color:{{ $colors[$i % count($colors)] }}">{{ $pct }}%</span>
            </div>
            <div class="aff-track">
              <div class="aff-fill" style="width:{{ $pct }}%;background:{{ $colors[$i % count($colors)] }}"></div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>

  {{-- ══ TRENDING + OPPORTUNITIES ══ --}}
  <div class="two-col anim-3">

    <div class="dash-card">
      <div class="dc-head">
        <span class="dc-title">Trending problems</span>
        <span class="lk-badge b-amber">{{ $topProblems->count() }}</span>
      </div>
      <div class="dc-body">
        @forelse($topProblems as $i => $problem)
          <div class="row-item">
            <span class="ri-rank">#{{ $i + 1 }}</span>
            <span class="ri-text">{{ $problem->title }}</span>
            <span class="ri-vote">▲ {{ $problem->votes_count }}</span>
          </div>
        @empty
          <div class="empty-state">
            <div class="empty-ico"><i data-lucide="inbox" style="width:16px;height:16px;"></i></div>
            <p class="empty-text">No trending problems yet.</p>
          </div>
        @endforelse
      </div>
    </div>

    <div class="dash-card">
      <div class="dc-head">
        <span class="dc-title">Generated DSS Ideas</span>
        <span class="lk-badge b-green">{{ $ideaCandidates->count() }}</span>
      </div>
      <div class="dc-body">
        @forelse($ideaCandidates as $category)
          <div class="row-item">
            <span class="ri-text">{{ $category->category }}</span>
            <span class="lk-badge b-green">{{ $category->total }} reports</span>
          </div>
        @empty
          <div class="empty-state">
            <div class="empty-ico"><i data-lucide="lightbulb" style="width:16px;height:16px;"></i></div>
            <p class="empty-text">No categories with enough reports yet.</p>
          </div>
        @endforelse
      </div>
    </div>

  </div>

  {{-- ══ PENDING APPROVALS ══ --}}
  <div class="dash-card anim-4">
    <div class="dc-head">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="dc-title">Pending approvals</span>
        @if($pendingFeedback->count())
          <span class="lk-badge b-red">{{ $pendingFeedback->count() }} pending</span>
        @endif
      </div>
    </div>
    <div class="dc-body">
      @forelse($pendingFeedback as $item)
        <div class="pending-item">
          <p class="pending-desc">{{ $item->description }}</p>
          @if($item->attachment_path)
            <div style="margin:10px 0">
              @if($item->attachment_type === 'image')
                <a href="{{ asset('storage/'.$item->attachment_path) }}" target="_blank" rel="noopener noreferrer" style="display:inline-block">
                  <img src="{{ asset('storage/'.$item->attachment_path) }}" alt="Supporting evidence" style="max-width:160px;max-height:105px;border-radius:12px;border:1px solid var(--border);object-fit:cover">
                </a>
              @else
                <a href="{{ asset('storage/'.$item->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="lk-badge b-green" style="text-decoration:none">View Evidence</a>
              @endif
            </div>
          @endif
          <div class="pending-foot">
            <span class="lk-badge b-amber">{{ $item->category }}</span>
            @if($item->is_flagged)
              <span class="lk-badge b-red">Flagged for review</span>
            @endif
            <div style="display:flex;gap:8px">
              <form method="POST" action="{{ route('feedback.approve', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-approve">Approve</button>
              </form>
              <form method="POST" action="{{ route('feedback.reject', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-reject">Reject</button>
              </form>
            </div>
          </div>
        </div>
      @empty
        <div class="empty-state">
          <div class="empty-ico"><i data-lucide="check-circle" style="width:16px;height:16px;color:var(--green);"></i></div>
          <p class="empty-text">All caught up — no pending feedback.</p>
        </div>
      @endforelse
    </div>
  </div>

  {{-- ══ RECENT SUBMISSIONS ══ --}}
  <div class="dash-card anim-5">
    <div class="dc-head">
      <span class="dc-title">Recent submissions</span>
      <span class="dc-sub">Latest activity</span>
    </div>
    <div class="dc-body">
      @forelse($recentFeedback as $feedback)
        <div class="recent-item">
          <div class="ri-dot"></div>
          <div class="ri-body">
            <div class="ri-bdesc">{{ $feedback->description }}</div>
            @if($feedback->attachment_path)
              <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" style="font-size:11.5px;font-weight:700;color:var(--amber);text-decoration:none">View Evidence</a>
            @endif
          </div>
          <span class="lk-badge b-amber" style="flex-shrink:0">{{ $feedback->category }}</span>
          <span class="ri-time">{{ $feedback->created_at->diffForHumans() }}</span>
        </div>
      @empty
        <div class="empty-state">
          <p class="empty-text">No recent submissions.</p>
        </div>
      @endforelse
    </div>
  </div>

</div>
{{-- The Chart.js block that used to sit here was pushed to a "scripts" stack
     that this layout never renders, so it never reached the browser. The
     category chart above is the server-rendered one that actually displays. --}}
@endsection
