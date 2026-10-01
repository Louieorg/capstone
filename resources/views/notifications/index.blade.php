@extends('layouts.app')

@section('title','Notifications')
@section('subtitle', 'Recent updates from your submitted problems and generated ideas')

@section('content')

<style>
html.dark {
  --surface: #13141a;
  --surface2: #1a1b23;
  --border: rgba(255,255,255,0.07);
  --border-h: rgba(251,176,52,0.28);
  --text: #f0f0f5;
  --text2: #9a9bb0;
  --text3: #5e6175;
  --amber: #fbb034;
  --adim: rgba(251,176,52,0.10);
  --amid: rgba(251,176,52,0.22);
}

html:not(.dark) {
  --surface: #ffffff;
  --surface2: #f9f8f6;
  --border: rgba(0,0,0,0.08);
  --border-h: rgba(186,117,23,0.35);
  --text: #111014;
  --text2: #5a5870;
  --text3: #9a97b0;
  --amber: #b57318;
  --adim: rgba(186,117,23,0.08);
  --amid: rgba(186,117,23,0.18);
}

.notif-list {
  max-width: 48rem;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notif-card {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  padding: 16px 18px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: var(--surface);
  transition: border-color .18s, transform .18s;
}

.notif-card:hover {
  border-color: var(--border-h);
  transform: translateY(-1px);
}

.notif-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--adim);
  border: 1px solid var(--amid);
  color: var(--amber);
  flex-shrink: 0;
}

/* Long unbroken notification text must wrap instead of widening the card. */
.notif-body {
  flex: 1;
  min-width: 0;
}

.notif-message {
  font-size: 14px;
  line-height: 1.7;
  color: var(--text);
  overflow-wrap: anywhere;
}

.notif-meta {
  margin-top: 6px;
  font-size: 12px;
  color: var(--text3);
}

.notif-empty {
  max-width: 48rem;
  margin: 0 auto;
  padding: 28px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text2);
  text-align: center;
}
</style>

<div class="notif-list">

@forelse($notifications as $notification)

@php
  $category = $notification->data['category'] ?? null;
  $ideaTitle = $notification->data['idea_title'] ?? null;
  $hasGeneratedIdeaTarget = filled($category) && filled($ideaTitle);
@endphp

@if($hasGeneratedIdeaTarget)
<a href="{{ route('notifications.redirect', $notification) }}" class="notif-card" style="text-decoration:none;color:inherit">
@else
<div class="notif-card">
@endif
  <div class="notif-icon" aria-hidden="true">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/>
      <path d="M9 17a3 3 0 0 0 6 0"/>
    </svg>
  </div>
  <div class="notif-body">
    <div class="notif-message">{{ $notification->data['message'] }}</div>
    <div class="notif-meta">
      {{ $notification->created_at->diffForHumans() }}
      @if($hasGeneratedIdeaTarget)
        • Click to open the generated capstone idea
      @endif
    </div>
  </div>
@if($hasGeneratedIdeaTarget)
</a>
@else
</div>
@endif

@empty

<div class="notif-empty">No notifications yet.</div>

@endforelse

</div>

@endsection
