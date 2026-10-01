<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/png" href="{{ asset('images/logolikha.png') }}">

  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('head')

  <script src="https://unpkg.com/lucide@latest"></script>

  @include('layouts.partials.styles')
</head>

<body
  x-data="{
    loginOpen: false,
    userOpen: false,
    collapsed: false
  }">

<div class="shell">

  <div class="app-body">

    {{-- ══ SIDEBAR (User) ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
      <a href="{{ route('home') }}" class="sb-brand">
        <img
            src="{{ asset('images/logolikha.png') }}"
            alt="LIKHA Logo"
            class="sb-brand-image"
        >
        <span class="sb-brand-text">LIKHA</span>
      </a>

      <nav class="sb-nav">
        <div class="sb-label">Main</div>

        <a href="{{ route('home') }}"
           class="nav-item {{ request()->routeIs('home') ? 'nav-active' : '' }}">
          <i data-lucide="home"></i>
          <span class="nav-label">Home Feed</span>
        </a>

        <a href="{{ route('feedback.create') }}"
           class="nav-item {{ request()->routeIs('feedback.create') ? 'nav-active' : '' }}">
          <i data-lucide="plus-circle"></i>
          <span class="nav-label">Submit Problem</span>
        </a>

        <a href="{{ route('discover') }}"
           class="nav-item {{ request()->routeIs('feedback.index') || request()->routeIs('discover') || request()->routeIs('feedback.show') ? 'nav-active' : '' }}">
          <i data-lucide="compass"></i>
          <span class="nav-label">Discover</span>
        </a>

        <a href="{{ route('capstone.opportunities') }}"
           class="nav-item {{ request()->routeIs('capstone.opportunities') ? 'nav-active' : '' }}">
          <i data-lucide="lightbulb"></i>
          <span class="nav-label">Capstone Opportunities</span>
        </a>

        @auth
          <div class="sb-divider"></div>

          <div class="sb-label">Personal</div>

          <a href="/my-ideas"
             class="nav-item {{ request()->is('my-ideas') ? 'nav-active' : '' }}">
            <i data-lucide="bookmark"></i>
            <span class="nav-label">Saved Ideas</span>
          </a>

          <a href="{{ route('profile.edit') }}"
             class="nav-item {{ request()->routeIs('profile.edit') ? 'nav-active' : '' }}">
            <i data-lucide="badge-check"></i>
            <span class="nav-label">My Contribution</span>
          </a>

          {{-- Admin, Adviser, and office entry points are not sidebar sections;
               they stay in the account menu at the bottom of this sidebar. --}}
        @endauth
      </nav>

      @auth
        @include('layouts.partials.account-menu', ['logoutFormId' => 'logoutForm', 'placement' => 'sidebar'])
      @endauth
    </aside>

    {{-- ══ MAIN ══ --}}
    <div class="app-main">
      {{-- The student shell has no top header row, so the notification control
           (and the guest actions) sit in the top-right of the content area. --}}
      <div class="app-topbar">
        @auth
          @include('layouts.partials.notifications')
        @else
          <button @click="loginOpen = true" class="h-btn-ghost">Sign in</button>
          <a href="{{ route('register') }}" class="h-btn-amber">Get started</a>
        @endauth
      </div>

      <main style="flex:1;overflow-y:auto">

        {{-- A full title bar is only rendered when the page still needs one
             (detail and settings views). Pages the sidebar already identifies fall
             back to a bare description line, or to no heading at all. --}}
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
        @else
          @hasSection('subtitle')
            <div class="page-lede">@yield('subtitle')</div>
          @endif
        @endif

        <div class="page-content">
          @yield('content')
        </div>

      </main>
    </div>

  </div>
</div>

{{-- ══ BOTTOM NAV (mobile) ══
     Five slots, mirroring the approved student IA: Home Feed, Discover, the
     Submit Problem CTA, Capstone Opportunities, and Profile. Profile is the
     mobile personal/account hub (Saved Ideas, My Contribution, profile
     information, password, theme, logout). --}}
<nav class="bottom-nav md:hidden">
  <a href="{{ route('home') }}" class="bn-item {{ request()->routeIs('home') ? 'bn-active' : '' }}">
    <i data-lucide="home"></i><span>Home</span>
  </a>
  <a href="{{ route('discover') }}" class="bn-item {{ request()->routeIs('feedback.index') || request()->routeIs('discover') || request()->routeIs('feedback.show') ? 'bn-active' : '' }}">
    <i data-lucide="compass"></i><span>Discover</span>
  </a>
  <a href="{{ route('feedback.create') }}" class="bn-center">
    <div class="bn-bubble">
      <i data-lucide="plus" style="width:22px;height:22px;color:#0a0b0f;"></i>
    </div>
    <span>Submit</span>
  </a>
  <a href="{{ route('capstone.opportunities') }}" class="bn-item {{ request()->routeIs('capstone.opportunities') ? 'bn-active' : '' }}">
    <i data-lucide="lightbulb"></i><span>Capstone</span>
  </a>
  @auth
    <a href="{{ route('profile.edit') }}" class="bn-item {{ request()->routeIs('profile.edit') ? 'bn-active' : '' }}">
      <i data-lucide="user-round"></i><span>Profile</span>
    </a>
  @else
    <button @click="loginOpen = true" class="bn-item">
      <i data-lucide="user"></i><span>Login</span>
    </button>
  @endauth
</nav>

@include('layouts.partials.login-modal')

{{-- Scripts --}}
@include('layouts.partials.scripts')
</body>
</html>