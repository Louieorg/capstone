@extends('layouts.office')

@section('title', 'Confirmation Requests')
@section('subtitle', 'Requests for the offices you currently represent')

@section('content')
@include('layouts.partials.design-system')

{{-- This view ships its own scoped styles: the office review page defines
     .or-* and the approve/reject buttons in its own <style> block, which this
     page never loaded, so the queue was rendering unstyled. --}}
<style>
  .or-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 18px 20px;
    transition: border-color .15s;
  }
  .or-card + .or-card { margin-top: 12px; }
  .or-card:hover { border-color: var(--amber-mid); }

  .or-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
  }
  .or-title {
    font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700;
    line-height: 1.35; color: var(--text);
    min-width: 0; overflow-wrap: break-word;
  }
  .or-badges { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

  /* Office attribution first: with several offices represented, every card has
     to say which office it belongs to. */
  .or-facts {
    display: grid; grid-template-columns: 108px minmax(0, 1fr);
    gap: 7px 14px; margin-top: 14px;
  }
  .or-fact-label {
    font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: var(--text3); padding-top: 2px;
  }
  .or-fact-value { font-size: 12.5px; color: var(--text2); min-width: 0; overflow-wrap: break-word; }
  .or-fact-value strong { color: var(--text); font-weight: 600; }

  .or-note {
    margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border);
    font-size: 11.5px; line-height: 1.6; color: var(--text3);
  }

  .or-evidence { margin-top: 14px; }
  .or-evidence-label {
    font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: var(--text3); margin-bottom: 6px;
  }
  .or-evidence-list { margin: 0; padding-left: 18px; font-size: 12.5px; line-height: 1.7; color: var(--text2); }
  .or-evidence-list a { color: var(--amber); text-decoration: none; }
  .or-evidence-list a:hover { text-decoration: underline; }

  .or-actions {
    display: flex; flex-wrap: wrap; align-items: center;
    justify-content: flex-end; gap: 10px; margin-top: 16px;
  }
  .or-actions form { margin: 0; }

  @media (max-width: 640px) {
    .or-card { padding: 16px; }
    .or-facts { grid-template-columns: minmax(0, 1fr); gap: 2px; }
    .or-fact-label { padding-top: 8px; }
    .or-actions { justify-content: stretch; }
    .or-actions form, .or-actions button { flex: 1; text-align: center; }
  }
</style>

<div class="mx-auto max-w-4xl space-y-3">

  @forelse($confirmationRequests as $confirmationRequest)
    <article class="or-card">
      <div class="or-head">
        <h2 class="or-title">{{ $confirmationRequest->ideaEvaluation->idea_title }}</h2>
        <div class="or-badges">
          <span class="lk-badge b-amber">Pending</span>
        </div>
      </div>

      <div class="or-facts">
        <span class="or-fact-label">Office</span>
        <span class="or-fact-value"><strong>{{ $confirmationRequest->office->name }}</strong></span>

        <span class="or-fact-label">Requested by</span>
        <span class="or-fact-value">
          <strong>{{ $confirmationRequest->requester->name }}</strong> &middot; group leader
        </span>

        <span class="or-fact-label">Requested</span>
        <span class="or-fact-value">
          {{ $confirmationRequest->created_at->diffForHumans() }}
          &middot; {{ $confirmationRequest->created_at->format('j M Y, H:i') }}
        </span>

        <span class="or-fact-label">Category</span>
        <span class="or-fact-value">{{ $confirmationRequest->ideaEvaluation->category }}</span>
      </div>

      @if($confirmationRequest->sourceFeedbacks->isNotEmpty())
        <div class="or-evidence">
          <div class="or-evidence-label">Source problem evidence</div>
          <ul class="or-evidence-list">
            @foreach($confirmationRequest->sourceFeedbacks as $feedback)
              <li><a href="{{ route('feedback.show', $feedback) }}">{{ $feedback->title }}</a></li>
            @endforeach
          </ul>
        </div>
      @endif

      <p class="or-note">
        Consult the office representative before requesting confirmation &mdash; consultation happens outside
        LIKHA, so this queue does not record or verify it, and the request does not reserve the opportunity.
      </p>

      <div class="or-actions">
        <form method="POST" action="{{ route('office.confirmations.decline', $confirmationRequest) }}">
          @csrf
          @method('PATCH')
          <button type="submit" class="btn-reject">Decline</button>
        </form>
        <form method="POST" action="{{ route('office.confirmations.confirm', $confirmationRequest) }}">
          @csrf
          @method('PATCH')
          <button type="submit" class="btn-approve">Confirm</button>
        </form>
      </div>
    </article>
  @empty
    <div class="empty-state or-card">
      <div class="empty-ico"><i data-lucide="badge-check" class="h-5 w-5" aria-hidden="true"></i></div>
      <p class="empty-text">No pending confirmation requests for the offices you currently represent.</p>
    </div>
  @endforelse

</div>
@endsection