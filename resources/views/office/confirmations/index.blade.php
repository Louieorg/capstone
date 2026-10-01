@extends('layouts.office')

@section('title', 'Office Confirmation Requests')
@section('subtitle', 'Requests for offices you currently represent')

@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    @forelse($confirmationRequests as $confirmationRequest)
        <article class="or-row">
            <div class="or-top">
                <div>
                    <h2 class="or-title">{{ $confirmationRequest->ideaEvaluation->idea_title }}</h2>
                    <p class="or-meta">
                        {{ $confirmationRequest->office->name }} &middot;
                        {{ $confirmationRequest->ideaEvaluation->category }} &middot;
                        Group leader: {{ $confirmationRequest->requester->name }}
                    </p>
                </div>
                <span class="lk-badge b-amber">Pending</span>
            </div>

            <div class="or-desc">
                <p class="font-semibold">Source problem evidence</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($confirmationRequest->sourceFeedbacks as $feedback)
                        <li><a class="underline" href="{{ route('feedback.show', $feedback) }}">{{ $feedback->title }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="or-foot">
                <span class="or-meta">Requested {{ $confirmationRequest->created_at->diffForHumans() }}</span>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('office.confirmations.confirm', $confirmationRequest) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-approve">Confirm</button>
                    </form>
                    <form method="POST" action="{{ route('office.confirmations.decline', $confirmationRequest) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-reject">Decline</button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="empty-state">
            <p class="empty-text">No pending confirmation requests for your active offices.</p>
        </div>
    @endforelse
</div>
@endsection