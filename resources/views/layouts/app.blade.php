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
    collapsed: localStorage.getItem('sidebar') === 'collapsed'
  }"
  x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? 'collapsed' : 'expanded'))">

<div class="shell">

  @include('layouts.partials.header')

  <div class="app-body">

    {{-- ══ SIDEBAR (User) ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
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

        <a href="{{ route('priority.index') }}"
   class="nav-item {{ request()->routeIs('priority.index') ? 'nav-active' : '' }}">
  <i data-lucide="alert-triangle"></i>
  <span class="nav-label">Priority Problems</span>
</a>

        <a href="{{ route('feedback.summary') }}"
           class="nav-item {{ request()->routeIs('feedback.summary') ? 'nav-active' : '' }}">
          <i data-lucide="bar-chart-3"></i>
          <span class="nav-label">Category Summary</span>
        </a>

        @auth
          <a href="{{ route('profile.edit') }}"
             class="nav-item {{ request()->routeIs('profile.edit') ? 'nav-active' : '' }}">
            <i data-lucide="badge-check"></i>
            <span class="nav-label">My Contribution</span>
          </a>

          <a href="/my-ideas"
             class="nav-item {{ request()->is('my-ideas') ? 'nav-active' : '' }}">
            <i data-lucide="bookmark"></i>
            <span class="nav-label">Saved Ideas</span>
          </a>

          {{--
  Admin and Adviser nav no longer live in the user sidebar — both have
  their own dedicated layouts. Entry points live in the header avatar
  dropdown (see layouts/partials/header.blade.php).
--}}
        @endauth
      </nav>

    </aside>

    {{-- ══ MAIN ══ --}}
    <div class="app-main">
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

{{-- ══ BOTTOM NAV (mobile) ══ --}}
<nav class="bottom-nav md:hidden">
  <a href="{{ route('landing') }}" class="bn-item {{ request()->routeIs('home') || request()->routeIs('landing') ? 'bn-active' : '' }}">
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
  <a href="{{ route('feedback.summary') }}" class="bn-item {{ request()->routeIs('feedback.summary') ? 'bn-active' : '' }}">
    <i data-lucide="bar-chart-3"></i><span>Summary</span>
  </a>
  @auth
    <a href="/my-ideas" class="bn-item {{ request()->is('my-ideas') ? 'bn-active' : '' }}">
      <i data-lucide="bookmark"></i><span>Saved</span>
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