<header class="app-header">

  {{-- Hamburger --}}
  <button @click="collapsed = !collapsed" class="h-hamburger hidden md:flex">
    <i data-lucide="menu" style="width:17px;height:17px;"></i>
  </button>

  {{-- Logo --}}
<a href="{{ route('home') }}" class="h-logo">
    <img
        src="{{ asset('images/logolikha.png') }}"
        alt="LIKHA Logo"
        class="h-logo-image"
    >
  <span class="h-logo-text">LIKHA</span>
</a>

  {{-- Search --}}
  <div class="h-search">
    <form method="GET" action="{{ route('discover') }}">
      <input type="text" name="search" placeholder="Search institutional problems…" value="{{ request('search') }}">
      <button type="submit">
        <i data-lucide="search" style="width:14px;height:14px;color:#7e8194;"></i>
      </button>
    </form>
  </div>

  {{-- Right --}}
  <div class="h-right">
    @auth
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
          class="h-icon-btn notif-trigger"
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

      <div x-data="{ open: false }" class="relative">
        <div class="h-avatar" @click="open = !open">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div x-show="open" @click.outside="open = false" x-transition x-cloak
          style="position:absolute;top:42px;right:0;width:180px;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;z-index:50;box-shadow:0 8px 24px rgba(0,0,0,.4)">
          <div style="padding:10px 14px;border-bottom:1px solid var(--border)">
            <div style="font-size:13px;font-weight:500;color:var(--text)">{{ auth()->user()->name }}</div>
            <div style="font-size:11px;color:var(--muted2)">{{ ucfirst(auth()->user()->role) }}</div>
          </div>
          <a href="{{ route('profile.edit') }}" class="sb-dd-item"><i data-lucide="user-round"></i> Profile</a>
          @if(auth()->user()->role === 'admin')
  <a href="{{ route('admin.dashboard') }}" class="sb-dd-item"><i data-lucide="shield"></i> Admin Panel</a>
@elseif(auth()->user()->role === 'adviser')
  <a href="{{ route('adviser.dashboard') }}" class="sb-dd-item"><i data-lucide="clipboard-check"></i> Adviser Panel</a>
@elseif(auth()->user()->isOfficeReviewer())
  <a href="{{ route('office.review.index') }}" class="sb-dd-item"><i data-lucide="clipboard-check"></i> {{ auth()->user()->officeLabel() }}</a>
@endif
          <button class="sb-dd-item" onclick="
            const d=document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme',d?'dark':'light')">
            <i data-lucide="sun"></i> Toggle theme
          </button>
          <div class="sb-dd-sep"></div>
          <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <button type="button" onclick="confirmLogout()" class="sb-dd-item danger">
              <i data-lucide="log-out"></i> Logout
            </button>
          </form>
        </div>
      </div>
    @endauth

    @guest
      <button @click="loginOpen = true" class="h-btn-ghost">Sign in</button>
      <a href="{{ route('register') }}" class="h-btn-amber">Get started</a>
    @endguest
  </div>

</header>