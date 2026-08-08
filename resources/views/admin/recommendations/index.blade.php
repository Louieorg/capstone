@extends('layouts.admin')

@section('title', 'Generated Recommendations')
@section('subtitle', 'Recommendations produced by the Decision Support System')

@section('content')

<style>
.rc-toolbar {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
}
.rc-search {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
}
.rc-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.rc-search svg { color: var(--muted2); flex-shrink: 0; }
.rc-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
}
.rc-btn {
  padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.rc-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.rc-tab {
  padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.rc-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.rc-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.rc-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-grey  { background: var(--surface2); color: var(--muted2); border-color: var(--border); }

.rc-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; transition: border-color .15s;
}
.rc-row:hover { border-color: var(--amber-mid); }
.rc-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
.rc-title { font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text); }
.rc-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.rc-scores { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 12px; }
.rc-score-block { display: flex; flex-direction: column; gap: 2px; }
.rc-score-label { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--muted2); }
.rc-score-val { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--text); }
.rc-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.btn-view { padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
  text-decoration: none; font-family: 'DM Sans', sans-serif; }

.rc-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ REVIEW STATUS TABS ══ --}}
  <div class="rc-tabs">
    <a href="{{ route('admin.recommendations.index', array_merge(request()->except('review', 'page'), ['review' => 'all'])) }}"
       class="rc-tab {{ $review === 'all' ? 'active' : '' }}">
      All <span class="rc-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('admin.recommendations.index', array_merge(request()->except('review', 'page'), ['review' => 'pending'])) }}"
       class="rc-tab {{ $review === 'pending' ? 'active' : '' }}">
      Awaiting Adviser Review <span class="rc-tab-count">{{ $counts['pending'] }}</span>
    </a>
    <a href="{{ route('admin.recommendations.index', array_merge(request()->except('review', 'page'), ['review' => 'reviewed'])) }}"
       class="rc-tab {{ $review === 'reviewed' ? 'active' : '' }}">
      Reviewed <span class="rc-tab-count">{{ $counts['reviewed'] }}</span>
    </a>
  </div>

  {{-- ══ SEARCH + FILTERS ══ --}}
  <form method="GET" action="{{ route('admin.recommendations.index') }}" class="rc-toolbar">
    @if(request('review'))
      <input type="hidden" name="review" value="{{ request('review') }}">
    @endif

    <div class="rc-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by idea title or category...">
    </div>

    <select name="category" class="rc-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>

    <select name="sort" class="rc-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
      <option value="overall_score" {{ $sort === 'overall_score' ? 'selected' : '' }}>Highest System Score</option>
      <option value="final_score" {{ $sort === 'final_score' ? 'selected' : '' }}>Highest Final Score</option>
    </select>

    <button type="submit" class="rc-btn">Apply</button>
  </form>

  {{-- ══ RECOMMENDATION LIST ══ --}}
  <div class="rc-list">
    @forelse($evaluations as $idea)
      <div class="rc-row">
        <div class="rc-top">
          <div>
            <div class="rc-title">{{ $idea->idea_title }}</div>
            <div class="rc-meta">{{ $idea->category }} &middot; Generated {{ $idea->created_at->diffForHumans() }}</div>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <span class="lk-badge {{ $idea->recommendation === 'Highly Recommended' ? 'b-green' : ($idea->recommendation === 'Recommended' ? 'b-amber' : 'b-grey') }}">
              {{ $idea->recommendation ?? 'Unrated' }}
            </span>
            @if(!is_null($idea->final_score))
              <span class="lk-badge b-blue">Adviser Reviewed</span>
            @else
              <span class="lk-badge b-grey">Awaiting Review</span>
            @endif
          </div>
        </div>

        <div class="rc-scores">
          <div class="rc-score-block">
            <span class="rc-score-label">System Score</span>
            <span class="rc-score-val">{{ number_format($idea->overall_score, 2) }}</span>
          </div>
          <div class="rc-score-block">
            <span class="rc-score-label">Feasibility</span>
            <span class="rc-score-val">{{ $idea->feasibility }}</span>
          </div>
          <div class="rc-score-block">
            <span class="rc-score-label">Impact</span>
            <span class="rc-score-val">{{ $idea->impact }}</span>
          </div>
          <div class="rc-score-block">
            <span class="rc-score-label">Complexity</span>
            <span class="rc-score-val">{{ $idea->complexity }}</span>
          </div>
          <div class="rc-score-block">
            <span class="rc-score-label">Innovation</span>
            <span class="rc-score-val">{{ $idea->innovation }}</span>
          </div>
          @if(!is_null($idea->final_score))
            <div class="rc-score-block">
              <span class="rc-score-label">Final Score (Adviser-Weighted)</span>
              <span class="rc-score-val" style="color:var(--blue)">{{ number_format($idea->final_score, 2) }}</span>
            </div>
          @endif
        </div>

        <div class="rc-foot">
          <div style="font-size:11.5px;color:var(--muted2)">
            @if(!is_null($idea->final_score))
              Adviser ratings — Feasibility: {{ $idea->adviser_feasibility }}, Impact: {{ $idea->adviser_impact }},
              Complexity: {{ $idea->adviser_complexity }}, Innovation: {{ $idea->adviser_innovation }}
            @else
              No adviser evaluation submitted yet.
            @endif
          </div>
          <a href="{{ route('feedback.category', $idea->category) }}#idea-{{ \Illuminate\Support\Str::slug($idea->idea_title) }}" class="btn-view" target="_blank" rel="noopener noreferrer">
            View Full Recommendation
          </a>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No generated recommendations match the current filters.</p>
      </div>
    @endforelse
  </div>

  {{-- ══ PAGINATION ══ --}}
  <div>
    {{ $evaluations->links() }}
  </div>

</div>

@endsection