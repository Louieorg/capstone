<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA Admin</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

   <link rel="icon" type="image/png" href="{{ asset('images/logolikha.png') }}">

  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('head')

  <script src="https://unpkg.com/lucide@latest"></script>

  @include('layouts.partials.styles')
  <style>
    /* Admin carries seven bottom-nav destinations. From 430px down each item is
       about 50px wide, so the labels are tightened and pinned to a single line
       to keep every destination readable without wrapping, clipping, or
       overlapping. Scoped to .bottom-nav-admin so the student bar is untouched. */
    @media (max-width: 430px) {
      .bottom-nav-admin { gap: 2px; padding-left: 2px; padding-right: 2px; }
      .bottom-nav-admin .bn-item { min-width: 0; padding: 0 1px; gap: 3px; }
      .bottom-nav-admin .bn-item span {
        font-size: 9px; line-height: 1.2; white-space: nowrap;
        max-width: 100%; overflow: hidden;
      }
      .bottom-nav-admin .bn-item svg { width: 18px; height: 18px; }
    }
  </style>
</head>

<body
  class="has-mobile-account"
  x-data="{ collapsed: false }">

<div class="shell">

  <div class="app-body">

    {{-- ══ SIDEBAR (Administrator) ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
      <a href="{{ route('admin.dashboard') }}" class="sb-brand">
        <img
            src="{{ asset('images/logolikha.png') }}"
            alt="LIKHA Logo"
            class="sb-brand-image"
        >
        <span class="sb-brand-text">LIKHA</span>
      </a>

      <nav class="sb-nav">
        <div class="sb-label">Main</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item {{ request()->routeIs('admin.dashboard') ? 'nav-active' : '' }}">
          <i data-lucide="layout-dashboard"></i>
          <span class="nav-label">Dashboard</span>
        </a>

        <a href="{{ route('admin.priority.index') }}"
   class="nav-item {{ request()->routeIs('admin.priority.*') ? 'nav-active' : '' }}">
  <i data-lucide="alert-triangle"></i>
  <span class="nav-label">Priority Problems</span>
</a>

        {{-- Feedback Management --}}
        <div x-data="{ open: {{ request()->routeIs('admin.feedback.*') ? 'true' : 'false' }} }">
          <button @click="open = !open" class="nav-group-btn" :class="{ 'nav-group-active': {{ request()->routeIs('admin.feedback.*') ? 'true' : 'false' }} }">
            <i data-lucide="inbox"></i>
            <span class="nav-label">Feedback Management</span>
            <i data-lucide="chevron-down" class="chevron" :style="open ? 'transform:rotate(180deg)' : ''"></i>
          </button>
          <div class="nav-subitems" x-show="open" x-collapse>
            <a href="{{ route('admin.feedback.index', ['status' => 'pending']) }}"
               class="nav-item {{ request()->routeIs('admin.feedback.index') && request('status') === 'pending' ? 'nav-active' : '' }}">
              <span class="nav-label">Pending Reviews</span>
            </a>
            <a href="{{ route('admin.feedback.index', ['status' => 'approved']) }}"
               class="nav-item {{ request()->routeIs('admin.feedback.index') && request('status') === 'approved' ? 'nav-active' : '' }}">
              <span class="nav-label">Approved Feedback</span>
            </a>
            <a href="{{ route('admin.feedback.index', ['status' => 'rejected']) }}"
               class="nav-item {{ request()->routeIs('admin.feedback.index') && request('status') === 'rejected' ? 'nav-active' : '' }}">
              <span class="nav-label">Rejected Feedback</span>
            </a>
            <a href="{{ route('admin.feedback.index') }}"
               class="nav-item {{ request()->routeIs('admin.feedback.index') && !request()->filled('status') ? 'nav-active' : '' }}">
              <span class="nav-label">All Feedback</span>
            </a>
          </div>
        </div>

        <a href="{{ route('admin.evidence') }}"
           class="nav-item {{ request()->routeIs('admin.evidence') ? 'nav-active' : '' }}">
          <i data-lucide="paperclip"></i>
          <span class="nav-label">Evidence</span>
        </a>

        {{-- Recommendation Management --}}
        <div x-data="{ open: {{ request()->routeIs('admin.recommendations.*') || request()->routeIs('admin.adviser-reviews.*') ? 'true' : 'false' }} }">
          <button @click="open = !open" class="nav-group-btn">
            <i data-lucide="lightbulb"></i>
            <span class="nav-label">Recommendation Management</span>
            <i data-lucide="chevron-down" class="chevron" :style="open ? 'transform:rotate(180deg)' : ''"></i>
          </button>
          <div class="nav-subitems" x-show="open" x-collapse>
            <a href="{{ Route::has('admin.recommendations.index') ? route('admin.recommendations.index') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.recommendations.*') ? 'nav-active' : '' }}">
              <span class="nav-label">Generated Recommendations</span>
            </a>
            <a href="{{ Route::has('admin.adviser-reviews.index') ? route('admin.adviser-reviews.index') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.adviser-reviews.*') ? 'nav-active' : '' }}">
              <span class="nav-label">Adviser Reviews</span>
            </a>
          </div>
        </div>

        {{-- Analytics --}}
        <div x-data="{ open: {{ request()->routeIs('admin.reports.*') || request()->routeIs('admin.analytics.*') ? 'true' : 'false' }} }">
          <button @click="open = !open" class="nav-group-btn">
            <i data-lucide="bar-chart-3"></i>
            <span class="nav-label">Analytics</span>
            <i data-lucide="chevron-down" class="chevron" :style="open ? 'transform:rotate(180deg)' : ''"></i>
          </button>
          <div class="nav-subitems" x-show="open" x-collapse>
            <a href="{{ Route::has('admin.reports.index') ? route('admin.reports.index') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.reports.*') ? 'nav-active' : '' }}">
              <span class="nav-label">Reports</span>
            </a>
            <a href="{{ Route::has('admin.analytics.index') ? route('admin.analytics.index') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'nav-active' : '' }}">
              <span class="nav-label">System Analytics</span>
            </a>
          </div>
        </div>

        {{-- User Management --}}
        <a href="{{ route('admin.users.index') }}"
           class="nav-item {{ request()->routeIs('admin.users.*') ? 'nav-active' : '' }}">
          <i data-lucide="users"></i>
          <span class="nav-label">User Management</span>
        </a>

        {{-- Reviewer-role assignment. The institutional Office directory is a
             separate, unrelated concept — see the Office Directory item below. --}}
        <a href="{{ route('admin.category-assignments.index') }}"
           class="nav-item {{ request()->routeIs('admin.category-assignments.*') ? 'nav-active' : '' }}">
          <i data-lucide="git-branch"></i>
          <span class="nav-label">Reviewer Assignments</span>
        </a>

        <a href="{{ route('admin.offices.index') }}"
           class="nav-item {{ request()->routeIs('admin.offices.*') ? 'nav-active' : '' }}">
          <i data-lucide="building-2"></i>
          <span class="nav-label">Office Directory</span>
        </a>

        {{-- Settings --}}
        <a href="{{ route('admin.settings') }}"
           class="nav-item {{ request()->routeIs('admin.settings') ? 'nav-active' : '' }}">
          <i data-lucide="settings"></i>
          <span class="nav-label">Settings</span>
        </a>

        <div class="sb-label">Back to LIKHA</div>
        <a href="{{ route('home') }}" class="nav-item">
          <i data-lucide="arrow-left"></i>
          <span class="nav-label">Exit Admin Panel</span>
        </a>
      </nav>

      {{-- Account. The top header row is gone, so the profile and session
           controls live at the bottom of the sidebar, as on the student shell. --}}
      @include('layouts.partials.account-menu', ['logoutFormId' => 'logoutForm', 'placement' => 'sidebar'])
    </aside>

    {{-- ══ MAIN ══ --}}
    <div class="app-main">
      {{-- No top header row: the notification control floats in the top-right of
           the content area, the same way the student shell does it. --}}
      <div class="app-topbar">
        @auth
          @include('layouts.partials.notifications')
        @endauth
      </div>

      <main style="flex:1;overflow-y:auto">

        @hasSection('title')
        <div class="page-bar">
          <div>
            <div class="page-title">@yield('title')</div>
            @hasSection('subtitle')
              <div class="page-sub">@yield('subtitle')</div>
            @endif
          </div>
          @hasSection('page-action')
            <div>@yield('page-action')</div>
          @endif
        </div>
        @endif

        <div class="page-content">
          @yield('content')
        </div>

      </main>
    </div>

  </div>
</div>

{{-- ══ BOTTOM NAV (mobile) — the sidebar is hidden below 768px ══ --}}
<nav class="bottom-nav bottom-nav-admin">
  <a href="{{ route('admin.dashboard') }}"
     class="bn-item {{ request()->routeIs('admin.dashboard') ? 'bn-active' : '' }}">
    <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('admin.priority.index') }}"
     class="bn-item {{ request()->routeIs('admin.priority.*') ? 'bn-active' : '' }}">
    <i data-lucide="alert-triangle"></i><span>Priority</span>
  </a>
  <a href="{{ route('admin.feedback.index') }}"
     class="bn-item {{ request()->routeIs('admin.feedback.*') ? 'bn-active' : '' }}">
    <i data-lucide="inbox"></i><span>Feedback</span>
  </a>
  <a href="{{ route('admin.evidence') }}"
     class="bn-item {{ request()->routeIs('admin.evidence') ? 'bn-active' : '' }}">
    <i data-lucide="paperclip"></i><span>Evidence</span>
  </a>
  <a href="{{ route('admin.category-assignments.index') }}"
     class="bn-item {{ request()->routeIs('admin.category-assignments.*') ? 'bn-active' : '' }}">
    <i data-lucide="git-branch"></i><span>Reviewers</span>
  </a>
  <a href="{{ route('admin.offices.index') }}"
     class="bn-item {{ request()->routeIs('admin.offices.*') ? 'bn-active' : '' }}">
    <i data-lucide="building-2"></i><span>Offices</span>
  </a>
  <a href="{{ route('home') }}" class="bn-item">
    <i data-lucide="arrow-left"></i><span>Exit</span>
  </a>
</nav>

{{-- The sidebar is hidden below 768px, so the same account menu is also
     reachable from a pill pinned above the mobile bottom nav. --}}
@auth
  @include('layouts.partials.account-menu', ['logoutFormId' => '', 'placement' => 'mobile'])
@endauth

{{-- Scripts --}}
@include('layouts.partials.scripts')
</body>
</html>