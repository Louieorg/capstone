@extends('layouts.admin')

@section('title', 'Settings')
@section('subtitle', 'Configure the Decision Support System thresholds')

@section('content')

<style>
.st-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px;
  max-width: 640px;
}
.st-card-title { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.st-card-sub { font-size: 12.5px; color: var(--muted2); line-height: 1.6; margin-bottom: 22px; }
.st-field { margin-bottom: 18px; }
.st-label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.st-hint { font-size: 11.5px; color: var(--muted2); margin-bottom: 8px; line-height: 1.5; }
.st-input {
  width: 160px; background: var(--surface2); border: 1px solid var(--border);
  border-radius: 9px; padding: 9px 12px; font-size: 14px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.st-btn {
  padding: 10px 22px; border-radius: 10px; font-size: 13.5px; font-weight: 600;
  background: var(--amber); color: #0a0b0f; border: none; cursor: pointer;
  font-family: 'DM Sans', sans-serif;
}
.st-btn:hover { filter: brightness(1.08); }
.st-warning {
  margin-top: 18px; padding: 12px 14px; border-radius: 10px;
  background: var(--red-bg); border: 1px solid var(--red-b);
  font-size: 12px; color: var(--red); line-height: 1.6;
}
</style>

<div class="st-card">
  <div class="st-card-title">Idea Generation Thresholds</div>
  <div class="st-card-sub">
    These two values control when the Decision Support System considers a problem cluster
    eligible for recommendation generation. Both conditions must be met.
  </div>

  <form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PATCH')

    <div class="st-field">
      <label class="st-label">Minimum Votes per Report</label>
      <div class="st-hint">A feedback report must receive at least this many votes to count toward idea generation.</div>
      <input type="number" name="minimum_votes_for_idea_generation" value="{{ old('minimum_votes_for_idea_generation', $minimumVotes) }}" min="1" max="1000" class="st-input" required>
      @error('minimum_votes_for_idea_generation')
        <div style="color:var(--red);font-size:11.5px;margin-top:4px">{{ $message }}</div>
      @enderror
    </div>

    <div class="st-field">
      <label class="st-label">Minimum Reports per Category</label>
      <div class="st-hint">A category must have at least this many qualifying reports before a recommendation is generated.</div>
      <input type="number" name="minimum_reports_for_idea_generation" value="{{ old('minimum_reports_for_idea_generation', $minimumReports) }}" min="1" max="1000" class="st-input" required>
      @error('minimum_reports_for_idea_generation')
        <div style="color:var(--red);font-size:11.5px;margin-top:4px">{{ $message }}</div>
      @enderror
    </div>

    <button type="submit" class="st-btn">Save Changes</button>
  </form>

  <div class="st-warning">
    <strong>Note:</strong> Lowering these values may generate new recommendations immediately for
    categories that were previously below threshold. Raising them does not remove recommendations
    that already exist.
  </div>
</div>

@endsection