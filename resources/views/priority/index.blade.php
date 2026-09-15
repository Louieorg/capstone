@extends('layouts.app')

@section('title', 'Priority Problems')
@section('subtitle', 'Institutionally escalated problems, ready for capstone research')

@section('content')

<style>
.pr-toolbar {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 14px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 18px;
}
.pr-search {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
}
.pr-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.pr-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
}
.pr-btn {
  padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}

.pr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; }

.pr-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 16px;
  padding: 20px; transition: border-color .2s, transform .2s;
}
.pr-card:hover { border-color: var(--amber-mid); transform: translateY(-2px); }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }

.pr-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.pr-title {
  font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--text);
  line-height: 1.4; margin-bottom: 10px;
}
.pr-desc {
  font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 14px;
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}

.pr-office {
  display: flex; align-items: center; gap: 10px;
  background: var(--amber-dim); border: 1px solid var(--amber-mid);
  border-radius: 12px; padding: 10px 14px; margin-bottom: 14px;
}
.pr-office-icon {
  width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
  background: var(--amber); color: #0a0b0f;
  display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;
}
.pr-office-text { min-width: 0; }
.pr-office-label { font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--amber); }
.pr-office-name { font-size: 13px; font-weight: 700; color: var(--text); }
.pr-office-contact { margin-top: 3px; font-size: 11.5px; color: var(--muted); }

.pr-foot { display: flex; align-items: center; justify-content: space-between; }
.pr-votes { font-size: 12px; color: var(--muted); }
.pr-link {
  font-size: 12.5px; font-weight: 600; color: var(--amber); text-decoration: none;
}

.empty-state { padding: 48px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; }
.empty-text { font-size: 14px; color: var(--muted); }
</style>

<div class="max-w-6xl mx-auto">

  {{-- ══ INTRO ══ --}}
  <div style="margin-bottom:18px">
    <p style="font-size:13px;color:var(--muted);line-height:1.6;max-width:720px">
      These problems were reported by an institutional office and carry official priority.
      Each card shows the office to approach and the representative who posted the problem, when available.
    </p>
  </div>

  {{-- ══ FILTERS ══ --}}
  <form method="GET" action="{{ route('priority.index') }}" class="pr-toolbar">
    <div class="pr-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search priority problems...">
    </div>
    <select name="category" class="pr-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $category)
        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
      @endforeach
    </select>
    <button type="submit" class="pr-btn">Search</button>
  </form>

  {{-- ══ PROBLEM GRID ══ --}}
  @if($problems->isEmpty())
    <div class="empty-state">
      <p class="empty-text">No priority problems match right now. Check back soon, or browse all problems on Discover.</p>
    </div>
  @else
    <div class="pr-grid">
      @foreach($problems as $problem)
        <div class="pr-card">
          <div class="pr-top">
            <span class="lk-badge b-amber">{{ $problem->category }}</span>
            <span class="lk-badge {{ $problem->priority_status === 'resolved' ? 'b-green' : ($problem->priority_status === 'taken' ? 'b-blue' : 'b-red') }}">
              {{ $problem->public_priority_status }}
            </span>
          </div>

          <div class="pr-title">{{ $problem->title }}</div>
          <p class="pr-desc">{{ $problem->description }}</p>

          @if($problem->priority_office)
            <div class="pr-office">
              <div class="pr-office-icon">{{ strtoupper(substr($problem->priority_office, 0, 2)) }}</div>
              <div class="pr-office-text">
                <div class="pr-office-label">Contact point</div>
                <div class="pr-office-name">{{ $problem->priority_office }}</div>
                <div class="pr-office-contact">
                  Posted by {{ $problem->is_anonymous ? 'Office representative' : ($problem->user->name ?? 'Office representative') }}
                </div>
              </div>
            </div>
          @endif

          <div class="pr-foot">
            <span class="pr-votes">&#9650; {{ $problem->votes_count }} supports</span>
            <a href="{{ route('feedback.show', $problem->id) }}" class="pr-link">View Full Problem &rarr;</a>
          </div>
        </div>
      @endforeach
    </div>

    <div style="margin-top:24px">{{ $problems->links() }}</div>
  @endif

</div>

@endsection