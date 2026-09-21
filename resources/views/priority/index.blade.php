@extends('layouts.app')

@section('title', 'Institutional Priorities')
@section('subtitle', 'Concerns identified by institutional offices that require attention and follow-up.')

@section('content')

@php
  $pageProblems = $problems->getCollection();
  $shownCount = $pageProblems->count();
  $pendingCount = $pageProblems->whereIn('priority_status', ['pending', 'taken'])->count();
  $resolvedCount = $pageProblems->where('priority_status', 'resolved')->count();
@endphp

<style>
  /* ── Priority summary ── */
  .ip-summary {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px;
  }
  .ip-stat {
    position: relative; overflow: hidden;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 14px 16px 14px 18px;
  }
  .ip-stat::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    background: var(--amber); opacity: .85;
  }
  .ip-stat-value {
    font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700;
    color: var(--text); line-height: 1.1;
  }
  .ip-stat-label {
    margin-top: 4px; font-size: 11px; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted);
  }
  .ip-summary-head {
    display: flex; align-items: baseline; justify-content: space-between;
    gap: 10px; flex-wrap: wrap; margin-bottom: 10px;
  }
  .ip-summary-caption {
    font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; color: var(--text);
  }
  .ip-summary-scope { font-size: 11px; color: var(--muted); }

  /* ── Toolbar ── */
  .ip-toolbar {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 18px;
  }
  .ip-search {
    flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
    background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  }
  .ip-search input {
    flex: 1; background: transparent; border: none; outline: none;
    color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
  }
  .ip-select {
    background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
    font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
  }
  .ip-btn {
    padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
    background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }

  /* ── Grid ── */
  .ip-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }

  /* ── Card ── */
  .ip-card {
    position: relative; display: flex; flex-direction: column;
    background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
    overflow: hidden; transition: border-color .2s, transform .2s, box-shadow .2s;
  }
  .ip-card:hover {
    border-color: var(--amber-mid); transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,.10), 0 0 0 1px var(--amber-mid);
  }
  html.dark .ip-card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,.5), 0 0 24px var(--amber-glow);
  }

  .ip-ribbon {
    display: flex; align-items: center; gap: 7px;
    padding: 9px 18px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
    color: var(--amber);
    background: linear-gradient(90deg, var(--amber-dim), transparent 75%);
    border-bottom: 1px solid var(--amber-mid);
  }
  .ip-ribbon-star { font-size: 12px; line-height: 1; }

  .ip-body { display: flex; flex-direction: column; flex: 1; padding: 16px 18px 18px; }

  .ip-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; flex-wrap: wrap; margin-bottom: 10px;
  }

  .lk-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; border: 1px solid;
  }
  .b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
  .b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
  .b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
  .b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }

  .ip-title {
    font-family: 'Sora', sans-serif; font-size: 15.5px; font-weight: 700;
    color: var(--text); line-height: 1.4; margin-bottom: 12px;
  }

  .ip-office {
    display: flex; align-items: center; gap: 12px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    border-radius: 12px; padding: 12px 14px; margin-bottom: 12px;
  }
  .ip-office-icon {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--amber), #f97316);
    color: #0a0b0f; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; box-shadow: 0 0 14px var(--amber-glow);
  }
  .ip-office-body { min-width: 0; }
  .ip-office-label {
    font-size: 10px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--amber);
  }
  .ip-office-name {
    font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text); margin-top: 1px;
  }
  .ip-office-sub { font-size: 11.5px; color: var(--muted); margin-top: 2px; }

  .ip-reporter { font-size: 11.5px; color: var(--muted); margin-bottom: 12px; }

  .ip-desc {
    font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 14px;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
  }

  .ip-foot {
    margin-top: auto; display: flex; align-items: center; justify-content: space-between;
    gap: 10px; padding-top: 12px; border-top: 1px solid var(--border);
  }
  .ip-votes { font-size: 12px; color: var(--muted); font-weight: 500; }
  .ip-link {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12.5px; font-weight: 700; color: var(--amber); text-decoration: none;
    transition: gap .15s;
  }
  .ip-link:hover { gap: 7px; }

  .ip-empty {
    padding: 48px; text-align: center;
    background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
  }
  .ip-empty-text { font-size: 14px; color: var(--muted); }

  @media (max-width: 560px) {
    .ip-summary { grid-template-columns: 1fr; }
    .ip-stat-value { font-size: 20px; }
  }
  @media (max-width: 480px) {
    .ip-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="max-w-6xl mx-auto">

  {{-- ══ PRIORITY SUMMARY (all three stats scoped to the priorities shown on this page) ══ --}}
  @if($problems->isNotEmpty())
    <div class="ip-summary-head">
      <span class="ip-summary-caption">Priority snapshot</span>
      <span class="ip-summary-scope">{{ $problems->hasPages() ? 'Reflects the priorities shown on this page' : 'Reflects all priorities listed' }}</span>
    </div>
    <div class="ip-summary">
      <div class="ip-stat">
        <div class="ip-stat-value">{{ $shownCount }}</div>
        <div class="ip-stat-label">Priorities shown</div>
      </div>
      <div class="ip-stat">
        <div class="ip-stat-value">{{ $pendingCount }}</div>
        <div class="ip-stat-label">Pending / under review</div>
      </div>
      <div class="ip-stat">
        <div class="ip-stat-value">{{ $resolvedCount }}</div>
        <div class="ip-stat-label">Resolved</div>
      </div>
    </div>
  @endif

  {{-- ══ FILTERS ══ --}}
  <form method="GET" action="{{ route('priority.index') }}" class="ip-toolbar">
    <div class="ip-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search institutional priorities...">
    </div>
    <select name="category" class="ip-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>
    <button type="submit" class="ip-btn">Search</button>
  </form>

  {{-- ══ PRIORITY GRID ══ --}}
  @if($problems->isEmpty())
    <div class="ip-empty">
      <p class="ip-empty-text">No institutional priorities match right now. Check back soon, or browse all problems on Discover.</p>
    </div>
  @else
    <div class="ip-grid">
      @foreach($problems as $problem)
        <article class="ip-card">
          <div class="ip-ribbon">
            <span class="ip-ribbon-star">★</span>
            <span>Institutional Priority</span>
          </div>

          <div class="ip-body">
            <div class="ip-head">
              <span class="lk-badge b-amber">{{ $problem->category }}</span>
              <span class="lk-badge {{ $problem->priority_status === 'resolved' ? 'b-green' : ($problem->priority_status === 'taken' ? 'b-blue' : 'b-red') }}">
                {{ $problem->public_priority_status }}
              </span>
            </div>

            <h3 class="ip-title">{{ $problem->title }}</h3>

            @if($problem->priority_office)
              <div class="ip-office">
                <div class="ip-office-icon">{{ strtoupper(substr($problem->priority_office, 0, 2)) }}</div>
                <div class="ip-office-body">
                  <div class="ip-office-label">Priority Office</div>
                  <div class="ip-office-name">{{ $problem->priority_office }}</div>
                  <div class="ip-office-sub">Office-identified concern</div>
                </div>
              </div>
            @endif

            <div class="ip-reporter">
              Reported by {{ $problem->is_anonymous ? 'Office representative' : ($problem->user->name ?? 'Office representative') }}
            </div>

            <p class="ip-desc">{{ $problem->description }}</p>

            <div class="ip-foot">
              <span class="ip-votes">&#9650; {{ $problem->votes_count }} supports</span>
              <a href="{{ route('feedback.show', $problem->id) }}" class="ip-link">View Concern &rarr;</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>

    <div style="margin-top:24px">{{ $problems->links() }}</div>
  @endif

</div>

@endsection