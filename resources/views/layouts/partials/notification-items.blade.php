@forelse($notifications as $notification)
  @php
    $category = $notification->data['category'] ?? null;
    $ideaTitle = $notification->data['idea_title'] ?? null;
    $hasGeneratedIdeaTarget = filled($category) && filled($ideaTitle);
  @endphp
  @if($hasGeneratedIdeaTarget)
    <a href="{{ route('notifications.redirect', $notification) }}" class="notif-item">
  @else
    <div class="notif-item">
  @endif
      <div class="notif-item-icon" aria-hidden="true">
        <i data-lucide="bell-ring" style="width:15px;height:15px;"></i>
      </div>
      <div class="notif-item-body">
        <div class="notif-item-message">{{ $notification->data['message'] ?? 'New notification' }}</div>
        <div class="notif-item-meta">
          <span>{{ $notification->created_at->diffForHumans() }}</span>
          @if($hasGeneratedIdeaTarget)
            <span class="notif-item-chip">Open generated capstone</span>
          @endif
          @if(is_null($notification->read_at))
            <span>Unread</span>
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
