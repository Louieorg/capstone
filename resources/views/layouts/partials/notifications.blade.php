{{-- Notification bell and dropdown.

     Shared by the standard top header and by the student layout, which renders it
     in the top-right of its main content area. The Alpine state, endpoint, and
     markup are unchanged; only the file it lives in moved. --}}
@php
  $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
@endphp
<div x-data="{
  open: false,
  loaded: false,
  loading: false,
  hasNotifications: false,
  async loadNotifications() {
    if (this.loaded || this.loading) {
      return;
    }

    this.loading = true;

    try {
      const response = await fetch('{{ route('notifications.dropdown') }}', {
        headers: { 'Accept': 'application/json' },
      });

      if (! response.ok) {
        throw new Error('Unable to load notifications.');
      }

      const data = await response.json();
      this.$refs.notificationList.innerHTML = data.html;
      this.hasNotifications = data.has_notifications;
      this.loaded = true;

      if (window.lucide) {
        window.lucide.createIcons();
      }
    } catch (error) {
      this.$refs.notificationList.innerHTML = '';
      const errorMessage = document.createElement('div');
      errorMessage.className = 'notif-empty';
      errorMessage.textContent = 'Unable to load notifications.';
      this.$refs.notificationList.append(errorMessage);
    } finally {
      this.loading = false;
    }
  }
}" class="relative">
  <button
    type="button"
    class="h-icon-btn notif-trigger{{ $unreadNotificationCount ? ' has-unread' : '' }}"
    :class="{ 'is-open': open }"
    @click="open = !open; if (open) loadNotifications()"
    :aria-expanded="open.toString()"
    aria-label="Toggle notifications">
    <i data-lucide="bell" style="width:16px;height:16px;"></i>
    @if($unreadNotificationCount)
      <span class="notif-badge">{{ $unreadNotificationCount }}</span>
    @endif
  </button>

  <div
    x-show="open"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    x-transition
    x-cloak
    class="notif-dropdown">
    <div class="notif-dropdown-head">
      <div>
        <div class="notif-dropdown-title">Notifications</div>
        <div class="notif-dropdown-sub">Recent updates from your ideas and reports</div>
      </div>
      @if($unreadNotificationCount)
        <span class="notif-item-chip">{{ $unreadNotificationCount }} new</span>
      @endif
    </div>

    <div class="notif-dropdown-list" x-ref="notificationList">
      <div class="notif-empty" x-show="loading">Loading notifications...</div>
    </div>

    <a href="{{ route('notifications') }}" class="notif-view-all" x-show="hasNotifications" x-cloak>View all notifications</a>
  </div>
</div>
