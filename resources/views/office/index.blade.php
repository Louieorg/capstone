@extends('layouts.office')

@section('title', $officeLabel . ' — Review Queue')
@section('subtitle', 'Reports in your office\'s assigned categories')

@section('content')

<style>
.or-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.or-tab {
  padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
}
.or-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.or-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
.or-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }

.or-scope {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 12px;
  padding: 12px 16px; margin-bottom: 16px; font-size: 12px; color: var(--muted);
}
.or-scope strong { color: var(--text); }

.lk-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
.b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.b-grey  { background: var(--surface2); color: var(--muted2); border-color: var(--border); }

.or-row { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 18px; margin-bottom: 10px; }
.or-row:hover { border-color: var(--amber-mid); }
.or-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
.or-title { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
.or-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.or-desc { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 12px; }
.or-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
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
.empty-state { padding: 40px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="max-w-6xl mx-auto">

  <div class="or-scope">
    Showing reports for: <strong>{{ $categories->count() ? implode(', ', $categories) : 'No categories assigned yet' }}</strong>
  </div>

  <div class="or-tabs">
    <a href="{{ route('office.review.index', ['status' => 'pending']) }}" class="or-tab {{ $status === 'pending' ? 'active' : '' }}">
      Pending <span class="or-tab-count">{{ $counts['pending'] }}</span>
    </a>
    <a href="{{ route('office.review.index', ['status' => 'approved']) }}" class="or-tab {{ $status === 'approved' ? 'active' : '' }}">
      Approved <span class="or-tab-count">{{ $counts['approved'] }}</span>
    </a>
    <a href="{{ route('office.review.index', ['status' => 'rejected']) }}" class="or-tab {{ $status === 'rejected' ? 'active' : '' }}">
      Rejected <span class="or-tab-count">{{ $counts['rejected'] }}</span>
    </a>
  </div>

  @forelse($problems as $item)
    <div class="or-row">
      <div class="or-top">
        <div>
          <div class="or-title">{{ $item->title }}</div>
          <div class="or-meta">
            {{ $item->category }} &middot;
            {{ $item->is_anonymous ? 'Anonymous' : ($item->user->name ?? 'Unknown') }} &middot;
            {{ $item->created_at->diffForHumans() }}
          </div>
        </div>
        <span class="lk-badge {{ $item->status === 'approved' ? 'b-green' : ($item->status === 'rejected' ? 'b-red' : 'b-grey') }}">
          {{ ucfirst($item->status) }}
        </span>
      </div>

      <p class="or-desc">{{ $item->description }}</p>

      <div class="or-foot">
        <div style="font-size:11.5px;color:var(--muted2)">
          @if($item->reviewed_by)
            Reviewed by {{ $item->reviewedBy->name ?? 'Unknown' }} &middot; {{ optional($item->reviewed_at)->diffForHumans() }}
          @else
            Not yet reviewed
          @endif
        </div>
        @if($item->status === 'pending')
          <div style="display:flex;gap:8px">
            <form method="POST" action="{{ route('office.review.approve', $item->id) }}">
              @csrf @method('PATCH')
              <button type="submit" class="btn-approve">Approve</button>
            </form>
            <form method="POST" action="{{ route('office.review.reject', $item->id) }}">
              @csrf @method('PATCH')
              <button type="submit" class="btn-reject">Reject</button>
            </form>
          </div>
        @endif
      </div>
    </div>
  @empty
    <div class="empty-state">
      <p class="empty-text">
        @if($categories->isEmpty())
          No categories have been assigned to your office yet. Contact the RDE Office.
        @else
          No reports in this status right now.
        @endif
      </p>
    </div>
  @endforelse

  <div style="margin-top:16px">{{ $problems->links() }}</div>

</div>

@endsection