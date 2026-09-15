@extends('layouts.admin')

@section('title', 'Office Assignments')
@section('subtitle', 'Map categories to the office responsible for validating them')

@section('content')

<style>
.ca-note {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 12px;
  padding: 14px 16px; margin-bottom: 18px; font-size: 12.5px; color: var(--muted); line-height: 1.6;
}
.ca-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
  padding: 14px 18px; display: flex; align-items: center; gap: 16px; margin-bottom: 8px;
}
.ca-category { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); flex: 1; }
.ca-select {
  background: var(--surface2); border: 1px solid var(--border); border-radius: 9px;
  padding: 7px 12px; font-size: 12.5px; color: var(--text); font-family: 'DM Sans', sans-serif;
  min-width: 240px;
}
.ca-current {
  font-size: 10.5px; font-weight: 600; padding: 3px 10px; border-radius: 999px;
  border: 1px solid var(--border); color: var(--muted2); min-width: 90px; text-align: center;
}
.ca-current.assigned { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
</style>

<div class="max-w-4xl mx-auto">

  <div class="ca-note">
    Categories left unassigned are visible only to the RDE Office's central review queue.
    Assigning a category here gives that office's account visibility and approval authority over reports in it —
    RDE retains full oversight of every report regardless of assignment.
  </div>

  @foreach($allCategories as $category)
    @php $current = $assignments[$category] ?? null; @endphp
    <div class="ca-row">
      <span class="ca-current {{ $current ? 'assigned' : '' }}">
        {{ $current === 'office_academic' ? 'Academic Affairs' : ($current === 'office_chief' ? 'Chief Admin' : 'Unassigned') }}
      </span>
      <span class="ca-category">{{ $category }}</span>
      <form method="POST" action="{{ route('admin.category-assignments.update') }}">
        @csrf
        <input type="hidden" name="category" value="{{ $category }}">
        <select name="office" class="ca-select" onchange="this.form.submit()">
          <option value="" {{ !$current ? 'selected' : '' }}>Unassigned (RDE only)</option>
          <option value="office_academic" {{ $current === 'office_academic' ? 'selected' : '' }}>Academic Affairs</option>
          <option value="office_chief" {{ $current === 'office_chief' ? 'selected' : '' }}>Chief Administrative Office</option>
        </select>
      </form>
    </div>
  @endforeach

  @if($allCategories->isEmpty())
    <div class="ca-note">No categories exist yet — they appear here once at least one report has been submitted.</div>
  @endif

</div>

@endsection