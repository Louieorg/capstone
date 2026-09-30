@extends('layouts.admin')

@section('title', 'User Management')
@section('subtitle', 'Search, filter, and manage user roles')

@section('content')

<style>
.mu-toolbar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px 18px;
  display: flex; flex-wrap: wrap; gap: 10px;
  align-items: center;
}
.mu-search {
  flex: 1; min-width: 220px;
  display: flex; align-items: center; gap: 8px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px; padding: 8px 12px;
}
.mu-search input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--text); font-size: 13px; font-family: 'DM Sans', sans-serif;
}
.mu-search svg { color: var(--muted2); flex-shrink: 0; }
.mu-select {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px; padding: 8px 12px;
  font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.mu-btn {
  padding: 8px 16px; border-radius: 10px;
  font-size: 12.5px; font-weight: 600;
  background: var(--amber-dim); color: var(--amber);
  border: 1px solid var(--amber-mid); cursor: pointer;
  font-family: 'DM Sans', sans-serif;
}
.mu-btn:hover { filter: brightness(1.08); }

.mu-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.mu-tab {
  padding: 7px 14px; border-radius: 999px;
  font-size: 12px; font-weight: 600;
  border: 1px solid var(--border);
  color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.mu-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.mu-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.mu-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
.b-grey  { background: var(--surface2); color: var(--muted2); border-color: var(--border); }

.mu-row {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 16px 18px;
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  transition: border-color .15s;
}
.mu-row:hover { border-color: var(--amber-mid); }
.mu-avatar {
  width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--amber), #f97316);
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; color: #0a0b0f;
}
.mu-identity { min-width: 180px; flex: 1; }
.mu-name { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
.mu-email { font-size: 12px; color: var(--muted2); }
.mu-stats { display: flex; gap: 14px; font-size: 11.5px; color: var(--muted2); min-width: 200px; }
.mu-joined { font-size: 11.5px; color: var(--muted2); }
.mu-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.mu-role-select {
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 9px; padding: 6px 10px; font-size: 12px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.btn-danger {
  padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
  background: transparent; color: var(--red); border: 1px solid var(--red-b);
  cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.mu-list { display: flex; flex-direction: column; gap: 10px; }
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
.you-tag { font-size: 10.5px; color: var(--amber); font-weight: 600; }
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ ROLE TABS ══ --}}
  <div class="mu-tabs">
    <a href="{{ route('admin.users.index', array_merge(request()->except('role', 'page'), ['role' => 'all'])) }}"
       class="mu-tab {{ $role === 'all' ? 'active' : '' }}">
      All <span class="mu-tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('admin.users.index', array_merge(request()->except('role', 'page'), ['role' => 'user'])) }}"
       class="mu-tab {{ $role === 'user' ? 'active' : '' }}">
      Users <span class="mu-tab-count">{{ $counts['user'] }}</span>
    </a>
    <a href="{{ route('admin.users.index', array_merge(request()->except('role', 'page'), ['role' => 'adviser'])) }}"
       class="mu-tab {{ $role === 'adviser' ? 'active' : '' }}">
      Advisers <span class="mu-tab-count">{{ $counts['adviser'] }}</span>
    </a>
    <a href="{{ route('admin.users.index', array_merge(request()->except('role', 'page'), ['role' => 'admin'])) }}"
       class="mu-tab {{ $role === 'admin' ? 'active' : '' }}">
      Admins <span class="mu-tab-count">{{ $counts['admin'] }}</span>
    </a>
  </div>

  {{-- ══ SEARCH + SORT ══ --}}
  <form method="GET" action="{{ route('admin.users.index') }}" class="mu-toolbar">
    @if(request('role'))
      <input type="hidden" name="role" value="{{ request('role') }}">
    @endif

    <div class="mu-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email...">
    </div>

    <select name="sort" class="mu-select" onchange="this.form.submit()">
      <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest First</option>
      <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
      <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name (A–Z)</option>
    </select>

    <button type="submit" class="mu-btn">Apply</button>
  </form>

  {{-- ══ USER LIST ══ --}}
  <div class="mu-list">
    @forelse($users as $user)
      <div class="mu-row">
        <div class="mu-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>

        <div class="mu-identity">
          <div class="mu-name">
            {{ $user->name }}
            @if($user->id === auth()->id())
              <span class="you-tag">(You)</span>
            @endif
          </div>
          <div class="mu-email">{{ $user->email }}</div>
        </div>

        <div class="mu-stats">
          <span>{{ $user->feedbacks_count }} reports</span>
          <span>{{ $user->votes_count }} votes</span>
          <span>{{ $user->comments_count }} comments</span>
        </div>

        <span class="lk-badge {{ $user->role === 'admin' ? 'b-amber' : ($user->role === 'adviser' ? 'b-blue' : 'b-grey') }}">
          {{ ucfirst($user->role) }}
        </span>

        <div class="mu-joined">Joined {{ $user->created_at->format('M d, Y') }}</div>

        <div class="mu-actions">
          @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.role', $user->id) }}" style="display:flex;gap:6px;align-items:center">
              @csrf @method('PATCH')
              <select name="role" class="mu-role-select" onchange="this.form.submit()">
  <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
  <option value="adviser" {{ $user->role === 'adviser' ? 'selected' : '' }}>Adviser</option>
  <option value="office_academic" {{ $user->role === 'office_academic' ? 'selected' : '' }}>Office — Academic Affairs</option>
  <option value="office_chief" {{ $user->role === 'office_chief' ? 'selected' : '' }}>Office — Chief Administrative</option>
  <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
</select>
            </form>
            <form method="POST" action="{{ route('admin.users.office-head', $user->id) }}" style="display:flex;gap:6px;align-items:center">
  @csrf @method('PATCH')
  <label style="display:flex;align-items:center;gap:4px;font-size:11.5px;color:var(--muted)">
    <input type="checkbox" name="is_office_head" value="1" {{ $user->is_office_head ? 'checked' : '' }} onchange="this.form.submit();">
    Office Head
  </label>
</form>

            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                  onsubmit="return confirm('Remove {{ $user->name }}\'s account? This cannot be undone.');">
              @csrf @method('DELETE')
              <button type="submit" class="btn-danger">Delete</button>
            </form>
          @else
            <span class="lk-badge b-grey">Current session</span>
          @endif
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No users match the current filters.</p>
      </div>
    @endforelse
  </div>

  {{-- ══ PAGINATION ══ --}}
  <div>
    {{ $users->links() }}
  </div>

</div>

@endsection