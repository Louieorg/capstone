@extends('layouts.admin')

@section('title', 'Reports')
@section('subtitle', 'Generate and export filtered CSV reports')

@section('content')

<style>
.rp-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.rp-tab {
  padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
  border: 1px solid var(--border); color: var(--muted); background: var(--surface);
  text-decoration: none; display: inline-flex; gap: 8px; align-items: center;
}
.rp-tab:hover { border-color: var(--amber-mid); color: var(--text); }
.rp-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }

.rp-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 22px;
}
.rp-card-title { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.rp-card-sub { font-size: 12.5px; color: var(--muted2); margin-bottom: 18px; }

.rp-field-row { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
.rp-field { flex: 1; min-width: 180px; }
.rp-label { display: block; font-size: 11.5px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
.rp-input {
  width: 100%; background: var(--surface2); border: 1px solid var(--border);
  border-radius: 9px; padding: 9px 12px; font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.rp-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 22px; border-radius: 10px; font-size: 13.5px; font-weight: 600;
  background: var(--amber); color: #0a0b0f; border: none; cursor: pointer;
  font-family: 'DM Sans', sans-serif;
}
.rp-btn:hover { filter: brightness(1.08); }

.rp-columns-note {
  margin-top: 16px; padding: 12px 14px; border-radius: 10px;
  background: var(--surface2); border: 1px solid var(--border);
  font-size: 12px; color: var(--muted2); line-height: 1.6;
}
</style>

<div class="max-w-4xl mx-auto" style="display:flex;flex-direction:column;gap:16px">

  {{-- ══ TYPE SELECTOR ══ --}}
  <div class="rp-tabs">
    <a href="{{ route('admin.reports.index', ['type' => 'feedback']) }}" class="rp-tab {{ $type === 'feedback' ? 'active' : '' }}">
      <i data-lucide="inbox" style="width:15px;height:15px;"></i> Feedback
    </a>
    <a href="{{ route('admin.reports.index', ['type' => 'recommendations']) }}" class="rp-tab {{ $type === 'recommendations' ? 'active' : '' }}">
      <i data-lucide="lightbulb" style="width:15px;height:15px;"></i> Generated Recommendations
    </a>
    <a href="{{ route('admin.reports.index', ['type' => 'adviser_reviews']) }}" class="rp-tab {{ $type === 'adviser_reviews' ? 'active' : '' }}">
      <i data-lucide="clipboard-check" style="width:15px;height:15px;"></i> Adviser Reviews
    </a>
  </div>

  {{-- ══ REPORT BUILDER ══ --}}
  <div class="rp-card">

    @if($type === 'feedback')
      <div class="rp-card-title">Feedback Report</div>
      <div class="rp-card-sub">Export submitted feedback with status, votes, and submitter details.</div>
    @elseif($type === 'recommendations')
      <div class="rp-card-title">Generated Recommendations Report</div>
      <div class="rp-card-sub">Export DSS-generated ideas with system and adviser scoring.</div>
    @else
      <div class="rp-card-title">Adviser Reviews Report</div>
      <div class="rp-card-sub">Export the full log of adviser evaluations and comments.</div>
    @endif

    <form method="GET" action="{{ route('admin.reports.export') }}">
      <input type="hidden" name="type" value="{{ $type }}">

      <div class="rp-field-row">
        <div class="rp-field">
          <label class="rp-label">Category</label>
          <select name="category" class="rp-input">
            <option value="">All Categories</option>
            @foreach($categories as $category)
              <option value="{{ $category }}">{{ $category }}</option>
            @endforeach
          </select>
        </div>

        @if($type === 'feedback')
          <div class="rp-field">
            <label class="rp-label">Status</label>
            <select name="status" class="rp-input">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        @endif

        @if($type === 'adviser_reviews')
          <div class="rp-field">
            <label class="rp-label">Recommendation</label>
            <select name="recommendation" class="rp-input">
              <option value="">All Recommendations</option>
              <option value="Recommended">Recommended</option>
              <option value="Needs Revision">Needs Revision</option>
              <option value="Not Recommended">Not Recommended</option>
            </select>
          </div>
        @endif
      </div>

      <div class="rp-field-row">
        <div class="rp-field">
          <label class="rp-label">From Date</label>
          <input type="date" name="date_from" class="rp-input">
        </div>
        <div class="rp-field">
          <label class="rp-label">To Date</label>
          <input type="date" name="date_to" class="rp-input">
        </div>
      </div>

      <button type="submit" class="rp-btn">
        <i data-lucide="download" style="width:15px;height:15px;"></i>
        Export CSV
      </button>
    </form>

    <div class="rp-columns-note">
      @if($type === 'feedback')
        <strong>Columns:</strong> ID, Title, Category, Department, Status, Flagged, Votes, Frequency, Affected Users, Submitted By, Submitted At
      @elseif($type === 'recommendations')
        <strong>Columns:</strong> ID, Idea Title, Category, Feasibility, Impact, Complexity, Innovation, Overall Score, System Recommendation, Adviser Feasibility, Adviser Impact, Adviser Complexity, Adviser Innovation, Final Score, Generated At
      @else
        <strong>Columns:</strong> ID, Idea Title, Category, Adviser, Recommendation, Comment, Reviewed At
      @endif
    </div>
  </div>

</div>

@endsection