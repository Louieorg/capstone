{{-- Account / profile control.

     Rendered in two places per shell so the same account menu is reachable on
     every viewport without introducing a second profile system:

       - inside the persistent sidebar (visible from 768px up)
       - as a pill above the mobile bottom nav (visible below 768px)

     Exactly one instance per page carries id="logoutForm", which is the form
     confirmLogout() submits. The other instance posts to the same logout route
     with its own CSRF token, so either Logout button signs the user out. --}}
@php
  $accountUser = auth()->user();
  // Resolved once: representation is a relationship on the active Office rows,
  // not a role. One query feeds both the label and the office names.
  $accountOffices = $accountUser->activeRepresentedOffices();
  $accountOfficeNames = $accountOffices->pluck('name');
  $accountRole = ($accountUser->role === 'user' && $accountOffices->isNotEmpty())
      ? 'Office Representative'
      : ($accountUser->isOfficeReviewer()
          ? ($accountUser->officeLabel() ?? ucfirst($accountUser->role))
          : ucfirst($accountUser->role));
  $accountLogoutFormId = $logoutFormId ?? 'logoutForm';
  $accountPlacement = $placement ?? 'sidebar';
@endphp


<div class="sb-account{{ $accountPlacement === 'mobile' ? ' sb-account--mobile' : '' }}" x-data="{ open: false }">
  <button type="button" class="sb-account-trigger"
          @click="open = !open" :class="{ 'is-open': open }"
          :aria-expanded="open.toString()" aria-haspopup="true"
          aria-label="Account menu for {{ $accountUser->name }}">
    <span class="h-avatar sb-account-avatar">{{ strtoupper(substr($accountUser->name, 0, 1)) }}</span>
    <span class="sb-account-meta">
      <span class="sb-account-name">{{ $accountUser->name }}</span>
      <span class="sb-account-role">{{ $accountRole }}</span>
      @foreach($accountOfficeNames as $accountOfficeName)
        <span class="sb-account-office">{{ $accountOfficeName }}</span>
      @endforeach
    </span>
    <i data-lucide="chevrons-up-down" class="sb-account-chevron"></i>
  </button>

  <div class="sb-account-menu"
       x-show="open" @click.outside="open = false"
       @keydown.escape.window="open = false" x-transition x-cloak>
    <div class="sb-account-head">
      <div class="sb-account-name">{{ $accountUser->name }}</div>
      <div class="sb-account-role">{{ $accountRole }}</div>
      @if($accountOfficeNames->isNotEmpty())
        <div class="sb-account-offices">
          @foreach($accountOfficeNames as $accountOfficeName)
            <div class="sb-account-office">{{ $accountOfficeName }}</div>
          @endforeach
        </div>
      @endif
    </div>
    <a href="{{ route('profile.edit') }}" class="sb-dd-item"><i data-lucide="user-round"></i> Profile</a>
    @if($accountUser->role === 'admin')
      <a href="{{ route('admin.dashboard') }}" class="sb-dd-item"><i data-lucide="shield"></i> Admin Panel</a>
    @elseif($accountUser->role === 'adviser')
      <a href="{{ route('adviser.dashboard') }}" class="sb-dd-item"><i data-lucide="clipboard-check"></i> Adviser Panel</a>
    @elseif($accountUser->isOfficeReviewer())
      <a href="{{ route('office.review.index') }}" class="sb-dd-item"><i data-lucide="clipboard-check"></i> {{ $accountUser->officeLabel() }}</a>
    @endif
    <button class="sb-dd-item" onclick="
      const d=document.documentElement.classList.toggle('dark');
      localStorage.setItem('theme',d?'dark':'light')">
      <i data-lucide="sun"></i> Toggle theme
    </button>
    <div class="sb-dd-sep"></div>
    <form method="POST" action="{{ route('logout') }}" @if(filled($accountLogoutFormId)) id="{{ $accountLogoutFormId }}" @endif>
      @csrf
      <button type="button" onclick="confirmLogout()" class="sb-dd-item danger">
        <i data-lucide="log-out"></i> Logout
      </button>
    </form>
  </div>
</div>
