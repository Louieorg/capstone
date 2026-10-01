@extends('layouts.admin')

@section('title', 'Evidence Management')
@section('subtitle', 'Files uploaded alongside student reports')

@section('content')

<style>
.ev-list { display: flex; flex-direction: column; gap: 10px; }

.ev-row {
  display: flex; align-items: center; justify-content: space-between;
  gap: 16px; flex-wrap: wrap;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 14px 18px;
  transition: border-color .15s;
}
.ev-row:hover { border-color: var(--amber-mid); }

.ev-main { display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1; }
.ev-thumb {
  height: 64px; width: 96px; flex-shrink: 0; object-fit: cover;
  border-radius: 8px; border: 1px solid var(--border);
}
.ev-thumb-file {
  height: 64px; width: 96px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px; border: 1px solid var(--border);
  background: var(--surface2); color: var(--muted);
  font-size: 11px; font-weight: 700; letter-spacing: .08em;
}
.ev-text { min-width: 0; }
.ev-name {
  font-family: 'Sora', sans-serif; font-size: 13.5px; font-weight: 700;
  color: var(--text); word-break: break-word;
}
.ev-meta { font-size: 11.5px; color: var(--muted2); margin-top: 2px; }
.ev-meta a { color: var(--amber); text-decoration: none; font-weight: 600; }
.ev-meta a:hover { text-decoration: underline; }

.ev-actions { display: flex; flex-wrap: wrap; gap: 8px; flex-shrink: 0; }
.ev-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 9px;
  font-size: 12px; font-weight: 600; text-decoration: none;
  font-family: 'DM Sans', sans-serif; cursor: pointer;
}
.ev-btn-primary { background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid); }
.ev-btn-primary:hover { filter: brightness(1.08); }
.ev-btn-danger { background: transparent; color: var(--red); border: 1px solid var(--red-b); }
.ev-btn-danger:hover { background: var(--red-bg); }

.empty-state {
  padding: 40px; text-align: center;
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
}
.empty-text { font-size: 13px; color: var(--muted2); }

@media (max-width: 640px) {
  .ev-actions { width: 100%; }
  .ev-actions form { flex: 1; }
  .ev-btn { width: 100%; justify-content: center; }
}
</style>

<div class="max-w-7xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  <div class="ev-list">
    @forelse($evidence as $item)
      <div class="ev-row">
        <div class="ev-main">
          @if($item->file_type === 'image')
            <img src="{{ asset('storage/'.$item->file_path) }}"
                 alt="Evidence file: {{ $item->file_name }}"
                 class="ev-thumb">
          @else
            <div class="ev-thumb-file">PDF</div>
          @endif
          <div class="ev-text">
            <div class="ev-name">{{ $item->file_name }}</div>
            <div class="ev-meta">
              Uploaded by {{ $item->user?->name ?? 'Guest' }} &middot; {{ $item->created_at->diffForHumans() }}
            </div>
            <div class="ev-meta">
              Report: <a href="{{ route('feedback.show', $item->feedback) }}">{{ $item->feedback->title }}</a>
            </div>
          </div>
        </div>
        <div class="ev-actions">
          <a href="{{ route('admin.evidence.download', $item) }}" class="ev-btn ev-btn-primary">Download</a>
          <form method="POST" action="{{ route('admin.evidence.destroy', $item) }}" onsubmit="return confirm('Delete this evidence?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="ev-btn ev-btn-danger">Delete</button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <p class="empty-text">No evidence has been uploaded yet.</p>
      </div>
    @endforelse
  </div>

  <div>{{ $evidence->links() }}</div>

</div>

@endsection
