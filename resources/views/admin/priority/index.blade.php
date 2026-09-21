@extends('layouts.admin')

@section('title', 'Priority Problems')
@section('subtitle', 'Escalated reports from office heads')

@section('content')

<style>
.pp-toolbar {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
}
.pp-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
}
.pp-btn {
  padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.pp-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.pp-tab {
  padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.pp-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.pp-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.pp-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }

.pp-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; transition: border-color .15s;
}
.pp-row:hover { border-color: var(--amber-mid); }
.pp-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
.pp-title { font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text); }
.pp-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.pp-desc {
  font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 12px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.pp-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.pp-info { font-size: 11.5px; color: var(--muted2); }
.pp-actions { display: flex; gap: 8px; }
.btn-take {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-resolve {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: var(--green-bg); color: var(--green); border: 1px solid var(--green-b);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.btn-reopen {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: transparent; color: var(--muted); border: 1px solid var(--border);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}

.pp-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-6xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ STATUS TABS ══ --}}
  <div class="pp-tabs">
    <a href="{{ route('admin.priority.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
       class="pp-tab {{ $status === 'all' ? 'active' : '' }}">
      All <span class="pp-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('admin.priority.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
       class="pp-tab {{ $status === 'pending' ? 'active' : '' }}">
      Pending <span class="pp-tab-count">{{ $counts['pending'] }}</span>
    </a>
    <a href="{{ route('admin.priority.index', array_merge(request()->except('status', 'page'), ['status' => 'taken'])) }}"
       class="pp-tab {{ $status === 'taken' ? 'active' : '' }}">
      Taken <span class="pp-tab-count">{{ $counts['taken'] }}</span>
    </a>
    <a href="{{ route('admin.priority.index', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}"
       class="pp-tab {{ $status === 'resolved' ? 'active' : '' }}">
      Resolved <span class="pp-tab-count">{{ $counts['resolved'] }}</span>
    </a>
  </div>
  

  {{-- ══ FILTERS ══ --}}
  <form method="GET" action="{{ route('admin.priority.index') }}" class="pp-toolbar">
    @if(request('status'))
      <input type="hidden" name="status" value="{{ request('status') }}">
    @endif

    <select name="department" class="pp-select" onchange="this.form.submit()">
      <option value="">All Departments</option>
      @foreach($departments as $department)
        <option value="{{ $department }}" {{ request('department') === $department ? 'selected' : '' }}>{{ $department }}</option>
      @endforeach
    </select>

    <select name="sort" class="pp-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
    </select>

    <button type="submit" class="pp-btn">Apply</button>
  </form>

  {{-- ══ PRIORITY LIST ══ --}}
  <div class="pp-list">
    @forelse($problems as $item)
      <div class="pp-row">
        <div class="pp-top">
          <div>
            <div class="pp-title">{{ $item->title }}</div>
            <div class="pp-meta">
              {{ $item->department ?? $item->category }} &middot;
              Reported by {{ $item->is_anonymous ? 'Office Head (anonymous)' : ($item->user->name ?? 'Unknown') }} &middot;
              {{ $item->created_at->diffForHumans() }}
            </div>
          </div>
          <span class="lk-badge {{ $item->priority_status === 'resolved' ? 'b-green' : ($item->priority_status === 'taken' ? 'b-blue' : 'b-red') }}">
            {{ ucfirst($item->priority_status) }}
          </span>
        </div>

        <p class="pp-desc">{{ $item->description }}</p>

        <div class="pp-foot">
          <div class="pp-info">
            @if($item->priority_status === 'taken' || $item->priority_status === 'resolved')
              Taken by {{ $item->takenBy->name ?? 'Unknown' }} &middot; {{ optional($item->priority_taken_at)->diffForHumans() }}
            @endif
            @if($item->priority_status === 'resolved')
              &middot; Resolved {{ optional($item->priority_resolved_at)->diffForHumans() }}
            @endif
            <span class="lk-badge b-amber" style="margin-left:6px">Feedback status: {{ ucfirst($item->status) }}</span>
          </div>
            @if($item->status === 'approved')
    <div style="margin-left:10px">
      @if($item->is_capstone_worthy)
        <span class="lk-badge b-green">Marked as Capstone Idea</span>
      @else
        <form method="POST" action="{{ route('admin.priority.mark-capstone', $item->id) }}">
          @csrf @method('PATCH')
          <button type="submit" class="btn-approve">Mark as Capstone Idea</button>
        </form>
      @endif
    </div>
  @endif
          <div class="pp-actions">
            @if($item->priority_status === 'pending')
              <form method="POST" action="{{ route('admin.priority.take', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-take">Take</button>
              </form>
            @elseif($item->priority_status === 'taken')
              <form method="POST" action="{{ route('admin.priority.resolve', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-resolve">Mark Resolved</button>
              </form>
              <form method="POST" action="{{ route('admin.priority.reopen', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-reopen">Reopen</button>
              </form>
            @else
              <form method="POST" action="{{ route('admin.priority.reopen', $item->id) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-reopen">Reopen</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No priority problems match the current filters.</p>
      </div>
    @endforelse
  </div>

  <div>{{ $problems->links() }}</div>

</div>

@endsection