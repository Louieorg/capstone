@extends('layouts.admin')

@section('title', 'Manage Feedback')
@section('subtitle', 'Search, filter, and moderate all submitted feedback')

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

/* ── Toolbar ── */
.mf-toolbar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px 18px;
  display: flex; flex-wrap: wrap; gap: 10px;
  align-items: center;
}
.mf-search {
  flex: 1; min-width: 220px;
  display: flex; align-items: center; gap: 8px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px; padding: 8px 12px;
}
.mf-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.mf-search svg { color: var(--text3); flex-shrink: 0; }
.mf-select {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.mf-btn {
  padding: 8px 16px; border-radius: 10px;
  font-size: 12.5px; font-weight: 600;
  background: var(--adim); color: var(--amber);
  border: 1px solid var(--amid); cursor: pointer;
  font-family: 'DM Sans', sans-serif;
}
.mf-btn:hover { filter: brightness(1.08); }

/* ── Status tabs ── */
.mf-tabs {
  display: flex; flex-wrap: wrap; gap: 8px;
}
.mf-tab {
  padding: 7px 14px; border-radius: 999px;
  font-size: 12px; font-weight: 600;
  border: 1px solid var(--border);
  color: var(--text2); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.mf-tab:hover { border-color: var(--amid); color: var(--text); }
.mf-tab.active {
  background: var(--adim); border-color: var(--amid); color: var(--amber);
}
.mf-tab-count {
  font-size: 11px; padding: 1px 6px; border-radius: 999px;
  background: rgba(0,0,0,0.08); color: inherit;
}

/* ── Badges ── */
.lk-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--adim);     color: var(--amber); border-color: var(--amid); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
.b-grey  { background: var(--surface2); color: var(--text3); border-color: var(--border); }

/* ── Feedback row card ── */
.fb-row {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 16px 18px;
  transition: border-color .15s;
}
.fb-row:hover { border-color: var(--border-h); }
.fb-top {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 12px; margin-bottom: 8px; flex-wrap: wrap;
}
.fb-title { font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text); }
.fb-meta { font-size: 11.5px; color: var(--text3); margin-top: 2px; }
.fb-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.fb-desc {
  font-size: 13px; color: var(--text2); line-height: 1.6;
  margin-bottom: 12px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}
.fb-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.fb-stats { display: flex; gap: 14px; font-size: 12px; color: var(--text3); }
.fb-actions { display: flex; gap: 8px; }
.btn-approve {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--green-bg); color: var(--green); border: 1px solid var(--green-b);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-reject {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: transparent; color: var(--red); border: 1px solid var(--red-b);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-view {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
  text-decoration: none; font-family: 'DM Sans', sans-serif;
}

.mf-list { display: flex; flex-direction: column; gap: 10px; }

.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--text3); }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ STATUS TABS ══ --}}
  <div class="mf-tabs">
    <a href="{{ route('admin.feedback.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
       class="mf-tab {{ $status === 'all' ? 'active' : '' }}">
      All <span class="mf-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('admin.feedback.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
       class="mf-tab {{ $status === 'pending' ? 'active' : '' }}">
      Pending <span class="mf-tab-count">{{ $counts['pending'] }}</span>
    </a>
    <a href="{{ route('admin.feedback.index', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}"
       class="mf-tab {{ $status === 'approved' ? 'active' : '' }}">
      Approved <span class="mf-tab-count">{{ $counts['approved'] }}</span>
    </a>
    <a href="{{ route('admin.feedback.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}"
       class="mf-tab {{ $status === 'rejected' ? 'active' : '' }}">
      Rejected <span class="mf-tab-count">{{ $counts['rejected'] }}</span>
    </a>
    <a href="{{ route('admin.feedback.index', array_merge(request()->except('flagged', 'page'), ['flagged' => 1])) }}"
       class="mf-tab {{ request()->boolean('flagged') ? 'active' : '' }}">
      Flagged <span class="mf-tab-count">{{ $counts['flagged'] }}</span>
    </a>
  </div>

  {{-- ══ SEARCH + FILTERS ══ --}}
  <form method="GET" action="{{ route('admin.feedback.index') }}" class="mf-toolbar">
    @if(request('status'))
      <input type="hidden" name="status" value="{{ request('status') }}">
    @endif
    @if(request('flagged'))
      <input type="hidden" name="flagged" value="1">
    @endif

    <div class="mf-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title or description...">
    </div>

    <select name="category" class="mf-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>

    <select name="sort" class="mf-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
      <option value="most_supported" {{ $sort === 'most_supported' ? 'selected' : '' }}>Most Supported</option>
    </select>

    <button type="submit" class="mf-btn">Apply</button>
  </form>

  {{-- ══ FEEDBACK LIST ══ --}}
  <div class="mf-list">
    @forelse($feedbacks as $item)
      <div class="fb-row">
        <div class="fb-top">
          <div>
            <div class="fb-title">{{ $item->title }}</div>
            <div class="fb-meta">
              {{ $item->is_anonymous ? 'Anonymous' : ($item->user->name ?? 'Unknown user') }}
              &middot; {{ $item->created_at->diffForHumans() }}
            </div>
          </div>
          <div class="fb-badges">
            <span class="lk-badge b-amber">{{ $item->category }}</span>
            <span class="lk-badge {{ $item->status === 'approved' ? 'b-green' : ($item->status === 'rejected' ? 'b-red' : 'b-grey') }}">
              {{ ucfirst($item->status) }}
            </span>
            @if($item->is_flagged)
              <span class="lk-badge b-red">Flagged</span>
            @endif
          </div>
        </div>

        <p class="fb-desc">{{ $item->description }}</p>

        <div class="fb-foot">
          <div class="fb-stats">
            <span>▲ {{ $item->votes_count }} supports</span>
            <span>{{ $item->comments_count }} comments</span>
            @if($item->attachment_path)
              <a href="{{ asset('storage/'.$item->attachment_path) }}" target="_blank" rel="noopener noreferrer" style="color:var(--amber);text-decoration:none;font-weight:600">View Evidence</a>
            @endif
          </div>
          <div class="fb-actions">
            @if($item->status === 'approved' && !$item->is_flagged)
              <a href="{{ route('feedback.show', $item->id) }}" class="btn-view">View</a>
            @endif
            @if($item->status !== 'approved')
              <form method="POST" action="{{ route('feedback.approve', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-approve">Approve</button>
              </form>
            @endif
            @if($item->status !== 'rejected')
              <form method="POST" action="{{ route('feedback.reject', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-reject">Reject</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No feedback matches the current filters.</p>
      </div>
    @endforelse
  </div>

  {{-- ══ PAGINATION ══ --}}
  <div>
    {{ $feedbacks->links() }}
  </div>

</div>

@endsection