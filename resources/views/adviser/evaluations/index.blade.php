@extends('layouts.adviser')

@section('title', 'Evaluation History')
@section('subtitle', 'Every evaluation you have submitted')

@section('content')

<style>
.eh-toolbar {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
}
.eh-search {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
}
.eh-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.eh-search svg { color: var(--muted2); flex-shrink: 0; }
.eh-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
}
.eh-btn {
  padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.eh-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.eh-tab {
  padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.eh-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.eh-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.eh-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }

.eh-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; transition: border-color .15s;
}
.eh-row:hover { border-color: var(--amber-mid); }
.eh-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
.eh-title { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
.eh-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.eh-comment { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 8px; }
.eh-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 11.5px; color: var(--muted2); }
.btn-view { padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
  text-decoration: none; font-family: 'DM Sans', sans-serif; }

.eh-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-5xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ RECOMMENDATION TABS ══ --}}
  <div class="eh-tabs">
    <a href="{{ route('adviser.evaluations.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => ''])) }}"
       class="eh-tab {{ !request()->filled('recommendation') ? 'active' : '' }}">
      All <span class="eh-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('adviser.evaluations.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Recommended'])) }}"
       class="eh-tab {{ request('recommendation') === 'Recommended' ? 'active' : '' }}">
      Recommended <span class="eh-tab-count">{{ $counts['recommended'] }}</span>
    </a>
    <a href="{{ route('adviser.evaluations.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Needs Revision'])) }}"
       class="eh-tab {{ request('recommendation') === 'Needs Revision' ? 'active' : '' }}">
      Needs Revision <span class="eh-tab-count">{{ $counts['needs_revision'] }}</span>
    </a>
    <a href="{{ route('adviser.evaluations.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Not Recommended'])) }}"
       class="eh-tab {{ request('recommendation') === 'Not Recommended' ? 'active' : '' }}">
      Not Recommended <span class="eh-tab-count">{{ $counts['not_recommended'] }}</span>
    </a>
  </div>

  {{-- ══ SEARCH + FILTERS ══ --}}
  <form method="GET" action="{{ route('adviser.evaluations.index') }}" class="eh-toolbar">
    @if(request('recommendation'))
      <input type="hidden" name="recommendation" value="{{ request('recommendation') }}">
    @endif

    <div class="eh-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by idea title or category...">
    </div>

    <select name="category" class="eh-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>

    <select name="sort" class="eh-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
    </select>

    <button type="submit" class="eh-btn">Apply</button>
  </form>

  {{-- ══ EVALUATION LIST ══ --}}
  <div class="eh-list">
    @forelse($reviews as $review)
      <div class="eh-row">
        <div class="eh-top">
          <div>
            <div class="eh-title">{{ $review->idea_title }}</div>
            <div class="eh-meta">{{ $review->category }} &middot; {{ $review->created_at->diffForHumans() }}</div>
          </div>
          <span class="lk-badge {{ $review->recommendation === 'Recommended' ? 'b-green' : ($review->recommendation === 'Needs Revision' ? 'b-amber' : 'b-red') }}">
            {{ $review->recommendation }}
          </span>
        </div>

        @if($review->comment)
          <p class="eh-comment">{{ $review->comment }}</p>
        @endif

        <div class="eh-foot">
          <span>Submitted {{ $review->created_at->format('M d, Y g:i A') }}</span>
          <a href="{{ route('feedback.category', $review->category) }}#idea-{{ \Illuminate\Support\Str::slug($review->idea_title) }}" class="btn-view" target="_blank" rel="noopener noreferrer">
            View Recommendation
          </a>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">You haven't submitted any evaluations yet.</p>
      </div>
    @endforelse
  </div>

  {{-- ══ PAGINATION ══ --}}
  <div>
    {{ $reviews->links() }}
  </div>

</div>

@endsection