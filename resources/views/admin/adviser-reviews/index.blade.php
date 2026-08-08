@extends('layouts.admin')

@section('title', 'Adviser Reviews')
@section('subtitle', 'Log of every evaluation submitted by advisers')

@section('content')

<style>
.ar-toolbar {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
}
.ar-search {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
}
.ar-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.ar-search svg { color: var(--muted2); flex-shrink: 0; }
.ar-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
}
.ar-btn {
  padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.ar-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.ar-tab {
  padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.ar-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.ar-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.ar-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-grey  { background: var(--surface2); color: var(--muted2); border-color: var(--border); }

.ar-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; transition: border-color .15s;
}
.ar-row:hover { border-color: var(--amber-mid); }
.ar-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
.ar-title { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
.ar-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.ar-comment { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 8px; }
.ar-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 11.5px; color: var(--muted2); }
.btn-view { padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
  text-decoration: none; font-family: 'DM Sans', sans-serif; }

.ar-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ RECOMMENDATION TABS ══ --}}
  <div class="ar-tabs">
    <a href="{{ route('admin.adviser-reviews.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => ''])) }}"
       class="ar-tab {{ !request()->filled('recommendation') ? 'active' : '' }}">
      All <span class="ar-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('admin.adviser-reviews.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Recommended'])) }}"
       class="ar-tab {{ request('recommendation') === 'Recommended' ? 'active' : '' }}">
      Recommended <span class="ar-tab-count">{{ $counts['recommended'] }}</span>
    </a>
    <a href="{{ route('admin.adviser-reviews.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Needs Revision'])) }}"
       class="ar-tab {{ request('recommendation') === 'Needs Revision' ? 'active' : '' }}">
      Needs Revision <span class="ar-tab-count">{{ $counts['needs_revision'] }}</span>
    </a>
    <a href="{{ route('admin.adviser-reviews.index', array_merge(request()->except('recommendation', 'page'), ['recommendation' => 'Not Recommended'])) }}"
       class="ar-tab {{ request('recommendation') === 'Not Recommended' ? 'active' : '' }}">
      Not Recommended <span class="ar-tab-count">{{ $counts['not_recommended'] }}</span>
    </a>
  </div>

  {{-- ══ SEARCH + FILTERS ══ --}}
  <form method="GET" action="{{ route('admin.adviser-reviews.index') }}" class="ar-toolbar">
    @if(request('recommendation'))
      <input type="hidden" name="recommendation" value="{{ request('recommendation') }}">
    @endif

    <div class="ar-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by idea title or category...">
    </div>

    <select name="category" class="ar-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>

    <select name="adviser" class="ar-select" onchange="this.form.submit()">
      <option value="">All Advisers</option>
      @foreach($advisers as $adviser)
        <option value="{{ $adviser->id }}" {{ (string) request('adviser') === (string) $adviser->id ? 'selected' : '' }}>{{ $adviser->name }}</option>
      @endforeach
    </select>

    <select name="sort" class="ar-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
    </select>

    <button type="submit" class="ar-btn">Apply</button>
  </form>

  {{-- ══ REVIEW LOG ══ --}}
  <div class="ar-list">
    @forelse($reviews as $review)
      <div class="ar-row">
        <div class="ar-top">
          <div>
            <div class="ar-title">{{ $review->idea_title }}</div>
            <div class="ar-meta">
              {{ $review->category }} &middot;
              Reviewed by {{ $review->user->name ?? 'Unknown adviser' }} &middot;
              {{ $review->created_at->diffForHumans() }}
            </div>
          </div>
          <span class="lk-badge {{ $review->recommendation === 'Recommended' ? 'b-green' : ($review->recommendation === 'Needs Revision' ? 'b-amber' : 'b-red') }}">
            {{ $review->recommendation }}
          </span>
        </div>

        @if($review->comment)
          <p class="ar-comment">{{ $review->comment }}</p>
        @endif

        <div class="ar-foot">
          <span>Submitted {{ $review->created_at->format('M d, Y g:i A') }}</span>
          <a href="{{ route('feedback.category', $review->category) }}#idea-{{ \Illuminate\Support\Str::slug($review->idea_title) }}" class="btn-view" target="_blank" rel="noopener noreferrer">
            View Recommendation
          </a>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No adviser reviews match the current filters.</p>
      </div>
    @endforelse
  </div>

  {{-- ══ PAGINATION ══ --}}
  <div>
    {{ $reviews->links() }}
  </div>

</div>

@endsection