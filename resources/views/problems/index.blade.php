@extends('layouts.app')

@section('title', 'Reported Problems')
@section('subtitle', 'Explore campus issues reported by students')

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
  --voted-bg: rgba(251,176,52,0.12);
  --voted-c:  #fbb034;
  --green:    #5fcd8a;
  --green-bg: rgba(95,205,138,0.10);
  --green-b:  rgba(95,205,138,0.22);
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
  --voted-bg: rgba(186,117,23,0.10);
  --voted-c:  #b57318;
  --green:    #15803d;
  --green-bg: rgba(22,163,74,0.08);
  --green-b:  rgba(22,163,74,0.20);
}

@keyframes fadeInUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.anim-in { animation: fadeInUp .5s ease both; }

/* Filter bar */
.filter-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px 18px;
  display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
  margin-bottom: 22px;
  transition: border-color .2s;
}
.filter-bar:focus-within { border-color: var(--amid); }

.lk-input {
  flex: 1; min-width: 160px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.lk-input::placeholder { color: var(--text3); }
.lk-input:focus {
  border-color: var(--amid);
  box-shadow: 0 0 0 3px var(--adim);
}

.lk-select {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
  outline: none; cursor: pointer;
  min-width: 160px;
  transition: border-color .15s;
}
.lk-select:focus {
  border-color: var(--amid);
  box-shadow: 0 0 0 3px var(--adim);
}

.btn-filter {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 20px; border-radius: 10px;
  background: var(--amber); color: #0a0b0f;
  font-size: 13px; font-weight: 600; border: none;
  cursor: pointer; font-family: 'DM Sans', sans-serif;
  white-space: nowrap; transition: all .15s;
}
.btn-filter:hover { filter: brightness(1.08); transform: translateY(-1px); }

/* Results header */
.results-head {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px; margin-bottom: 14px;
}
.results-count { font-size: 12px; color: var(--text3); font-weight: 500; }
.sort-row { display: flex; align-items: center; gap: 6px; }
.sort-lbl { font-size: 11.5px; color: var(--text3); }
.sort-opt {
  padding: 4px 11px; border-radius: 8px;
  font-size: 11.5px; font-weight: 600; cursor: pointer;
  border: 1px solid var(--border); background: transparent; color: var(--text2);
  font-family: 'DM Sans', sans-serif; transition: all .15s;
}
.sort-opt:hover { border-color: var(--amid); color: var(--amber); }

/* Problem cards */
.problem-list { display: flex; flex-direction: column; gap: 10px; }

.p-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 18px 20px;
  transition: border-color .2s, transform .2s;
}
.p-card:hover { border-color: var(--border-h); transform: translateY(-2px); }

.p-card-top {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px; margin-bottom: 10px;
}
.p-tags { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }

.p-cat {
  display: inline-flex; align-items: center;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600;
  background: var(--adim); border: 1px solid var(--amid); color: var(--amber);
}
.badge-candidate {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600;
  background: var(--green-bg); color: var(--green); border: 1px solid var(--green-b);
}
.p-time { font-size: 11px; color: var(--text3); }

.p-desc {
  font-size: 13.5px; color: var(--text);
  line-height: 1.72; margin-bottom: 14px;
}

.p-footer {
  display: flex; align-items: center;
  justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.p-actions { display: flex; align-items: center; gap: 10px; }

.vote-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 13px; border-radius: 9px;
  border: 1px solid var(--border); background: transparent;
  font-size: 12.5px; font-weight: 600; color: var(--text2);
  font-family: 'DM Sans', sans-serif; cursor: pointer;
  transition: all .15s;
}
.vote-btn:hover { border-color: var(--amid); color: var(--amber); }
.vote-btn.voted {
  background: var(--voted-bg);
  border-color: var(--amid);
  color: var(--voted-c);
}

.vote-count { font-size: 12px; color: var(--text3); }
.vote-count strong { color: var(--text2); font-weight: 600; }

.p-link {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 12.5px; font-weight: 600;
  color: var(--amber); text-decoration: none;
  transition: opacity .15s;
}
.p-link:hover { opacity: .8; }

/* Empty state */
.empty-state {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 52px; text-align: center;
}
.empty-icon {
  width: 40px; height: 40px; border-radius: 12px;
  background: var(--adim); border: 1px solid var(--amid);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 12px; color: var(--amber);
}
.empty-text { font-size: 13.5px; color: var(--text3); }

/* Pagination */
.pagination {
  display: flex; align-items: center; justify-content: center;
  gap: 6px; margin-top: 24px; flex-wrap: wrap;
}

/* Override Laravel's default pagination to match our tokens */
.pagination nav { display: flex; align-items: center; gap: 6px; }
.pagination nav > div:first-child { display: none; }
.pagination span[aria-current="page"] > span,
.pagination a {
  display: flex; align-items: center; justify-content: center;
  min-width: 34px; height: 34px; padding: 0 10px;
  border-radius: 9px; border: 1px solid var(--border);
  background: transparent; color: var(--text2);
  font-size: 13px; font-weight: 600; text-decoration: none;
  font-family: 'DM Sans', sans-serif; transition: all .15s;
}
.pagination a:hover { border-color: var(--amid); color: var(--amber); }
.pagination span[aria-current="page"] > span {
  background: var(--adim); border-color: var(--amid); color: var(--amber);
}
.pagination span.disabled span {
  display: flex; align-items: center; justify-content: center;
  min-width: 34px; height: 34px;
  border-radius: 9px; border: 1px solid var(--border);
  color: var(--text3); font-size: 13px; opacity: .5;
}

@media (max-width: 640px) {
  .lk-input, .lk-select, .btn-filter { width: 100%; }
}
</style>

<div class="max-w-5xl mx-auto anim-in">

  {{-- Filter bar --}}
  <form method="GET" action="{{ route('feedback.index') }}" class="filter-bar">

    <div style="position:relative;flex:2;min-width:180px">
      <input
        type="text" name="search"
        value="{{ request('search') }}"
        placeholder="Search campus problems…"
        class="lk-input"
        style="padding-left:38px;width:100%">
      <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none">
        <i data-lucide="search" style="width:14px;height:14px;"></i>
      </span>
    </div>

    <select name="category" class="lk-select">
      <option value="">All Categories</option>
      @foreach($categories as $cat)
        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
          {{ $cat }}
        </option>
      @endforeach
    </select>

    <button type="submit" class="btn-filter">
      <i data-lucide="sliders-horizontal" style="width:13px;height:13px;"></i>
      Filter
    </button>

    @if(request('search') || request('category'))
      <a href="{{ route('feedback.index') }}"
         style="font-size:12.5px;color:var(--text3);text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:4px;transition:color .15s"
         onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text3)'">
        <i data-lucide="x" style="width:13px;height:13px;"></i>
        Clear
      </a>
    @endif

  </form>

  {{-- Results header --}}
  @if(!$feedbacks->isEmpty())
  <div class="results-head">
    <span class="results-count">
      Showing {{ $feedbacks->firstItem() }}–{{ $feedbacks->lastItem() }}
      of {{ $feedbacks->total() }} problems
    </span>
    <div class="sort-row">
      <span class="sort-lbl">Sort:</span>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'votes']) }}"
         class="sort-opt {{ request('sort', 'votes') === 'votes' ? 'active' : '' }}"
         style="{{ request('sort', 'votes') === 'votes' ? 'background:var(--adim);border-color:var(--amid);color:var(--amber)' : '' }}">
        Most voted
      </a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'latest']) }}"
         class="sort-opt {{ request('sort') === 'latest' ? 'active' : '' }}"
         style="{{ request('sort') === 'latest' ? 'background:var(--adim);border-color:var(--amid);color:var(--amber)' : '' }}">
        Newest
      </a>
    </div>
  </div>
  @endif

  {{-- Empty state --}}
  @if($feedbacks->isEmpty())
    <div class="empty-state">
      <div class="empty-icon">
        <i data-lucide="inbox" style="width:18px;height:18px;"></i>
      </div>
      <p class="empty-text">No problems found matching your search.</p>
      <a href="{{ route('feedback.index') }}"
         style="display:inline-block;margin-top:12px;font-size:12.5px;font-weight:600;color:var(--amber);text-decoration:none">
        Clear filters →
      </a>
    </div>

  @else

    {{-- Problem list --}}
    <div class="problem-list">
      @foreach($feedbacks as $feedback)

      @php $hasVoted = $feedback->votes->count() > 0; @endphp

      <div class="p-card">

        {{-- Top row --}}
        <div class="p-card-top">
          <div class="p-tags">
            <span class="p-cat">{{ $feedback->category }}</span>
            @if($feedback->is_idea_candidate ?? false)
              <span class="badge-candidate">
                <i data-lucide="check-circle" style="width:10px;height:10px;"></i>
                Idea Candidate
              </span>
            @endif
          </div>
          <span class="p-time">{{ $feedback->created_at->diffForHumans() }}</span>
        </div>

        {{-- Description --}}
        <p class="p-desc">{{ $feedback->description }}</p>

        @if($feedback->attachment_path)
          <div style="margin:12px 0">
            @if($feedback->attachment_type === 'image')
              <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" style="display:inline-block">
                <img src="{{ asset('storage/'.$feedback->attachment_path) }}" alt="Supporting evidence for {{ $feedback->title }}" style="max-width:180px;max-height:120px;border-radius:12px;border:1px solid var(--border);object-fit:cover">
              </a>
            @else
              <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="p-link" style="display:inline-flex">
                <i data-lucide="paperclip" style="width:13px;height:13px;"></i>
                View Evidence
              </a>
            @endif
          </div>
        @endif

        {{-- Footer --}}
        <div class="p-footer">
          <div class="p-actions">

            <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
              @csrf
              <button type="submit" class="vote-btn {{ $hasVoted ? 'voted' : '' }}">
                <i data-lucide="{{ $hasVoted ? 'triangle' : 'triangle' }}"
                   style="width:11px;height:11px;{{ $hasVoted ? 'fill:currentColor' : '' }}"></i>
                {{ $hasVoted ? 'Voted' : 'Upvote' }}
              </button>
            </form>

            <span class="vote-count">
              <strong>{{ $feedback->votes_count }}</strong> votes
            </span>

          </div>

          <a href="{{ route('feedback.category', $feedback->category) }}" class="p-link">
            View related
            <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
          </a>
        </div>

      </div>
      @endforeach
    </div>

  @endif

  {{-- Pagination --}}
  @if($feedbacks->hasPages())
    <div class="pagination">
      {{ $feedbacks->withQueryString()->links() }}
    </div>
  @endif

</div>
@endsection
