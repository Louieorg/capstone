@extends('layouts.app')

@section('title', 'Adviser Dashboard')
@section('subtitle', 'Review AI-generated capstone ideas and track your evaluations')

@section('content')

<style>
html.dark {
  --surface:    #13141a;
  --surface2:   #1a1b23;
  --border:     rgba(255,255,255,0.07);
  --border-h:   rgba(251,176,52,0.28);
  --text:       #f0f0f5;
  --text2:      #9a9bb0;
  --text3:      #5e6175;
  --amber:      #fbb034;
  --adim:       rgba(251,176,52,0.10);
  --amid:       rgba(251,176,52,0.22);
  --green:      #5fcd8a;
  --green-bg:   rgba(95,205,138,0.10);
  --green-b:    rgba(95,205,138,0.22);
  --red:        #f87171;
  --red-bg:     rgba(248,113,113,0.10);
  --red-b:      rgba(248,113,113,0.22);
  --blue:       #60a5fa;
  --blue-bg:    rgba(96,165,250,0.10);
  --blue-b:     rgba(96,165,250,0.20);
  --prog-track: rgba(255,255,255,0.06);
}
html:not(.dark) {
  --surface:    #ffffff;
  --surface2:   #f9f8f6;
  --border:     rgba(0,0,0,0.08);
  --border-h:   rgba(186,117,23,0.35);
  --text:       #111014;
  --text2:      #5a5870;
  --text3:      #9a97b0;
  --amber:      #b57318;
  --adim:       rgba(186,117,23,0.08);
  --amid:       rgba(186,117,23,0.18);
  --green:      #15803d;
  --green-bg:   rgba(22,163,74,0.08);
  --green-b:    rgba(22,163,74,0.20);
  --red:        #dc2626;
  --red-bg:     rgba(220,38,38,0.08);
  --red-b:      rgba(220,38,38,0.20);
  --blue:       #1d4ed8;
  --blue-bg:    rgba(29,78,216,0.08);
  --blue-b:     rgba(29,78,216,0.18);
  --prog-track: rgba(0,0,0,0.07);
}

@keyframes fadeInUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}
.anim-1 { animation: fadeInUp .4s ease both; }
.anim-2 { animation: fadeInUp .4s .07s ease both; }
.anim-3 { animation: fadeInUp .4s .14s ease both; }
.anim-4 { animation: fadeInUp .4s .21s ease both; }

/* ── Stat grid ── */
.stat-grid {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 14px;
}
@media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }

.s-card {
  background: var(--surface); border: 1px solid var(--border);
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
.s-icon.red   { background: var(--red-bg);   border-color: var(--red-b);   color: var(--red); }
.s-icon.green { background: var(--green-bg); border-color: var(--green-b); color: var(--green); }
.s-icon.blue  { background: var(--blue-bg);  border-color: var(--blue-b);  color: var(--blue); }
.s-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: .08em;
  text-transform: uppercase; color: var(--text3); margin-bottom: 7px;
}
.s-val {
  font-family: 'Sora', sans-serif; font-size: 32px; font-weight: 800;
  line-height: 1; color: var(--amber); margin-bottom: 4px;
}
.s-card:not(.featured) .s-val { color: var(--text); }
.s-hint { font-size: 11.5px; color: var(--text3); }
.prog-bar {
  height: 5px; border-radius: 999px;
  background: var(--prog-track); overflow: hidden; margin-top: 8px;
}
.prog-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(to right, #fbb034, #f97316);
  transition: width .6s ease;
}
html:not(.dark) .prog-fill {
  background: linear-gradient(to right, #b57318, #c2410c);
}

/* ── Main layout ── */
.main-grid {
  display: grid; grid-template-columns: 1fr 300px; gap: 18px; align-items: start;
}
@media (max-width: 900px) { .main-grid { grid-template-columns: 1fr; } }
.left-col  { display: flex; flex-direction: column; gap: 14px; }
.right-col { display: flex; flex-direction: column; gap: 14px; }

/* ── Dash card ── */
.dash-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden; transition: border-color .2s;
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
.dc-body { padding: 14px 16px; }

/* ── Badge ── */
.lk-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid; white-space: nowrap;
}
.b-amber { background: var(--adim);     color: var(--amber); border-color: var(--amid); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }

/* ── Category row ── */
.cat-row {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 13px; border-radius: 11px;
  border: 1px solid var(--border);
  text-decoration: none; color: inherit;
  transition: border-color .15s, background .15s, transform .15s;
  margin-bottom: 7px;
}
.cat-row:last-child { margin-bottom: 0; }
.cat-row:hover {
  border-color: var(--amid);
  background: var(--adim);
  transform: translateX(3px);
}
.cat-icon {
  width: 34px; height: 34px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cat-icon.pending { background: var(--adim); border: 1px solid var(--amid); color: var(--amber); }
.cat-icon.done    { background: var(--green-bg); border: 1px solid var(--green-b); color: var(--green); }
.cat-name {
  font-family: 'Sora', sans-serif; font-size: 13px;
  font-weight: 700; color: var(--text); margin-bottom: 2px;
  transition: color .15s;
}
.cat-row:hover .cat-name { color: var(--amber); }
.cat-sub { font-size: 11.5px; color: var(--text3); }
.cat-arrow {
  font-size: 14px; color: var(--text3); margin-left: auto; flex-shrink: 0;
  transition: color .15s, transform .15s;
}
.cat-row:hover .cat-arrow { color: var(--amber); transform: translateX(3px); }

/* ── Review row ── */
.review-row {
  padding: 14px 16px; border-radius: 11px;
  border: 1px solid var(--border);
  transition: border-color .15s; margin-bottom: 8px;
}
.review-row:last-child { margin-bottom: 0; }
.review-row:hover { border-color: var(--amid); }
.rr-top {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 10px; margin-bottom: 6px;
}
.rr-title {
  font-family: 'Sora', sans-serif; font-size: 13px;
  font-weight: 700; color: var(--text); line-height: 1.4;
}
.rr-comment {
  font-size: 12.5px; color: var(--text2); line-height: 1.6; margin-bottom: 8px;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.rr-foot {
  display: flex; align-items: center; justify-content: space-between;
}
.rr-time { font-size: 11px; color: var(--text3); }

/* ── Recommendation breakdown ── */
.rec-row { margin-bottom: 14px; }
.rec-row:last-child { margin-bottom: 0; }
.rec-meta {
  display: flex; align-items: center;
  justify-content: space-between; margin-bottom: 5px;
}
.rec-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12.5px; color: var(--text2);
}
.rec-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rec-count { font-size: 12.5px; font-weight: 700; color: var(--text); }
.rec-pct { font-size: 10.5px; color: var(--text3); margin-top: 3px; text-align: right; }
.divider { height: 1px; background: var(--border); margin: 14px 0; }

/* ── Tip card ── */
.tip-card {
  border-radius: 14px; padding: 18px;
  border: 1px solid var(--amid); background: var(--adim);
}
.tip-label {
  font-size: 10.5px; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--amber); margin-bottom: 12px;
}
.tip-list { display: flex; flex-direction: column; gap: 9px; }
.tip-item { display: flex; gap: 9px; font-size: 12.5px; color: var(--text2); line-height: 1.55; }
.tip-num {
  font-family: 'Sora', sans-serif; font-size: 11.5px;
  font-weight: 700; color: var(--amber); flex-shrink: 0; margin-top: 1px;
}

/* ── Progress card ── */
.prog-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden; transition: border-color .2s;
}
.prog-card:hover { border-color: var(--border-h); }
.prog-big {
  font-family: 'Sora', sans-serif; font-size: 28px;
  font-weight: 800; color: var(--amber); line-height: 1;
}
.prog-sub { font-size: 12px; color: var(--text3); margin-top: 3px; margin-bottom: 12px; }
.prog-bar-lg { height: 8px; border-radius: 999px; background: var(--prog-track); overflow: hidden; }
.prog-fill-lg {
  height: 100%; border-radius: 999px;
  background: linear-gradient(to right, #fbb034, #f97316);
  transition: width .6s ease;
}
html:not(.dark) .prog-fill-lg { background: linear-gradient(to right, #b57318, #c2410c); }

/* ── Empty state ── */
.empty-state { padding: 28px; text-align: center; }
.empty-ico {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 10px; color: var(--amber);
}
.empty-text { font-size: 13px; color: var(--text3); }
</style>

<div class="max-w-6xl mx-auto" style="display:flex;flex-direction:column;gap:18px">

  {{-- ══ STAT CARDS ══ --}}
  <div class="stat-grid anim-1">

    <div class="s-card featured">
      <div class="s-icon">
        <i data-lucide="layers" style="width:15px;height:15px;"></i>
      </div>
      <div class="s-label">Ready to Review</div>
      <div class="s-val">{{ $readyCategories->count() }}</div>
      <div class="s-hint">categories with ideas</div>
    </div>

    <div class="s-card">
      <div class="s-icon red">
        <i data-lucide="clock" style="width:15px;height:15px;"></i>
      </div>
      <div class="s-label">Awaiting Review</div>
      <div class="s-val">{{ $pendingCategories->count() }}</div>
      <div class="s-hint">not yet reviewed</div>
    </div>

    <div class="s-card">
      <div class="s-icon green">
        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
      </div>
      <div class="s-label">Reviewed</div>
      <div class="s-val">{{ $reviewedCategories->count() }}</div>
      <div class="s-hint">categories done</div>
    </div>

    <div class="s-card">
      <div class="s-icon blue">
        <i data-lucide="trending-up" style="width:15px;height:15px;"></i>
      </div>
      <div class="s-label">Completion</div>
      @php
        $pct = $readyCategories->count() > 0
          ? round(($reviewedCategories->count() / $readyCategories->count()) * 100)
          : 0;
      @endphp
      <div class="s-val">{{ $pct }}%</div>
      <div class="prog-bar">
        <div class="prog-fill" style="width:{{ $pct }}%"></div>
      </div>
    </div>

  </div>

  {{-- ══ MAIN GRID ══ --}}
  <div class="main-grid">

    {{-- LEFT: categories + reviews --}}
    <div class="left-col">

      {{-- Needs review --}}
      <div class="dash-card anim-2">
        <div class="dc-head">
          <span class="dc-title">Needs your review</span>
          @if($pendingCategories->count())
            <span class="lk-badge b-red">{{ $pendingCategories->count() }} pending</span>
          @endif
        </div>
        <div class="dc-body">
          @forelse($pendingCategories as $cat)
            <a href="{{ route('feedback.category', $cat->category) }}" class="cat-row">
              <div class="cat-icon pending">
                <i data-lucide="folder-open" style="width:14px;height:14px;"></i>
              </div>
              <div style="flex:1;min-width:0">
                <div class="cat-name">{{ $cat->category }}</div>
                <div class="cat-sub">{{ $cat->total_reports }} {{ Str::plural('report', $cat->total_reports) }}</div>
              </div>
              <span class="lk-badge b-red">Unreviewed</span>
              <span class="cat-arrow">→</span>
            </a>
          @empty
            <div class="empty-state">
              <div class="empty-ico">
                <i data-lucide="check-circle" style="width:16px;height:16px;color:var(--green);"></i>
              </div>
              <p class="empty-text">All categories reviewed — great work!</p>
            </div>
          @endforelse
        </div>
      </div>

      {{-- Already reviewed --}}
      @if($reviewedCategories->count())
      <div class="dash-card anim-3">
        <div class="dc-head">
          <span class="dc-title">Already reviewed</span>
          <span class="lk-badge b-green">{{ $reviewedCategories->count() }} done</span>
        </div>
        <div class="dc-body">
          @foreach($reviewedCategories as $cat)
            @php $eval = $evaluations[$cat->category] ?? null; @endphp
            <a href="{{ route('feedback.category', $cat->category) }}" class="cat-row">
              <div class="cat-icon done">
                <i data-lucide="check" style="width:14px;height:14px;"></i>
              </div>
              <div style="flex:1;min-width:0">
                <div class="cat-name">{{ $cat->category }}</div>
                <div class="cat-sub">
                  {{ $cat->total_reports }} {{ Str::plural('report', $cat->total_reports) }}
                  @if($eval)
                    · Score: <strong style="color:var(--amber)">{{ $eval->final_score ?? $eval->overall_score }}</strong>
                  @endif
                </div>
              </div>
              <span class="lk-badge b-green">Reviewed</span>
            </a>
          @endforeach
        </div>
      </div>
      @endif

      {{-- Recent reviews --}}
      <div class="dash-card anim-4">
        <div class="dc-head">
          <span class="dc-title">Recent reviews</span>
          <span class="dc-sub">Latest activity</span>
        </div>
        <div class="dc-body">
          @forelse($recentReviews as $review)
            <div class="review-row">
              <div class="rr-top">
                <div class="rr-title">{{ $review->idea_title }}</div>
                @php
                  $recBadge = $review->recommendation === 'Recommended'
                    ? 'b-green' : ($review->recommendation === 'Needs Revision' ? 'b-amber' : 'b-red');
                @endphp
                <span class="lk-badge {{ $recBadge }}" style="flex-shrink:0">
                  {{ $review->recommendation }}
                </span>
              </div>
              <p class="rr-comment">{{ $review->comment }}</p>
              <div class="rr-foot">
                <span class="lk-badge b-blue">{{ $review->category }}</span>
                <span class="rr-time">{{ $review->created_at->diffForHumans() }}</span>
              </div>
            </div>
          @empty
            <div class="empty-state">
              <div class="empty-ico">
                <i data-lucide="clipboard-list" style="width:16px;height:16px;"></i>
              </div>
              <p class="empty-text">No reviews submitted yet. Start by reviewing a category above.</p>
            </div>
          @endforelse
        </div>
      </div>

    </div>

    {{-- RIGHT: stats + tip + progress --}}
    <div class="right-col">

      {{-- Recommendation breakdown --}}
      <div class="dash-card anim-2">
        <div class="dc-head">
          <span class="dc-title">My recommendations</span>
        </div>
        <div class="dc-body">
          @if($totalReviewed > 0)
            @foreach([
              'Recommended'     => ['var(--green)',  'linear-gradient(to right,var(--green),#22c55e)'],
              'Needs Revision'  => ['var(--amber)',  'linear-gradient(to right,#fbb034,#f97316)'],
              'Not Recommended' => ['var(--red)',    'linear-gradient(to right,var(--red),#f87171)'],
            ] as $label => [$dotColor, $fillGrad])
              @php
                $count = $recommendationStats[$label] ?? 0;
                $pctR  = $totalReviewed > 0 ? round(($count / $totalReviewed) * 100) : 0;
              @endphp
              <div class="rec-row">
                <div class="rec-meta">
                  <span class="rec-label">
                    <span class="rec-dot" style="background:{{ $dotColor }}"></span>
                    {{ $label }}
                  </span>
                  <span class="rec-count">{{ $count }}</span>
                </div>
                <div class="prog-bar">
                  <div class="prog-fill" style="width:{{ $pctR }}%;background:{{ $fillGrad }}"></div>
                </div>
                <div class="rec-pct">{{ $pctR }}%</div>
              </div>
            @endforeach

            <div class="divider"></div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="font-size:12px;color:var(--text3)">Total reviews</span>
              <span style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:var(--amber)">
                {{ $totalReviewed }}
              </span>
            </div>
          @else
            <div class="empty-state">
              <div class="empty-ico">
                <i data-lucide="bar-chart-2" style="width:16px;height:16px;"></i>
              </div>
              <p class="empty-text">Your review summary will appear here once you start evaluating ideas.</p>
            </div>
          @endif
        </div>
      </div>

      {{-- How to review tip --}}
      <div class="tip-card">
        <div class="tip-label">How to review</div>
        <div class="tip-list">
          <div class="tip-item">
            <span class="tip-num">1.</span>
            <span>Click a pending category from the list</span>
          </div>
          <div class="tip-item">
            <span class="tip-num">2.</span>
            <span>Read the AI-generated top idea and its explanation</span>
          </div>
          <div class="tip-item">
            <span class="tip-num">3.</span>
            <span>Click <strong style="color:var(--amber)">Add Review</strong> and fill in your scores</span>
          </div>
          <div class="tip-item">
            <span class="tip-num">4.</span>
            <span>Your score merges with the system score for a final rating</span>
          </div>
        </div>
      </div>

      {{-- Overall progress --}}
      <div class="prog-card">
        <div class="dc-head" style="border-bottom:1px solid var(--border)">
          <span class="dc-title">Overall progress</span>
          @php
            $totalCats = $readyCategories->count();
            $doneCats  = $reviewedCategories->count();
            $leftCats  = $pendingCategories->count();
            $overallPct = $totalCats > 0 ? round(($doneCats / $totalCats) * 100) : 0;
          @endphp
          @if($leftCats > 0)
            <span class="lk-badge b-red">{{ $leftCats }} left</span>
          @else
            <span class="lk-badge b-green">Complete!</span>
          @endif
        </div>
        <div style="padding:18px">
          <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:10px">
            <div>
              <div class="prog-big">{{ $doneCats }} / {{ $totalCats }}</div>
              <div class="prog-sub">categories reviewed</div>
            </div>
            <span style="font-family:'Sora',sans-serif;font-size:20px;font-weight:800;color:var(--amber)">
              {{ $overallPct }}%
            </span>
          </div>
          <div class="prog-bar-lg">
            <div class="prog-fill-lg" style="width:{{ $overallPct }}%"></div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

@endsection