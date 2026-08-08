<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA Adviser</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('head')

  <script src="https://unpkg.com/lucide@latest"></script>

  @include('layouts.partials.styles')
</head>

<body
  x-data="{
    collapsed: localStorage.getItem('sidebar') === 'collapsed'
  }"
  x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? 'collapsed' : 'expanded'))">

<div class="shell">

  @include('layouts.partials.header')

  <div class="app-body">

    {{-- ══ SIDEBAR (Adviser) ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
      <nav class="sb-nav">
        <div class="sb-label">Main</div>

        <a href="{{ route('adviser.dashboard') }}"
           class="nav-item {{ request()->routeIs('adviser.dashboard') ? 'nav-active' : '' }}">
          <i data-lucide="layout-dashboard"></i>
          <span class="nav-label">Dashboard</span>
        </a>

        <a href="{{ Route::has('adviser.evaluations.index') ? route('adviser.evaluations.index') : '#' }}"
           class="nav-item {{ request()->routeIs('adviser.evaluations.*') ? 'nav-active' : '' }}">
          <i data-lucide="history"></i>
          <span class="nav-label">Evaluation History</span>
        </a>

        <div class="sb-label">Back to LIKHA</div>
        <a href="{{ route('home') }}" class="nav-item">
          <i data-lucide="arrow-left"></i>
          <span class="nav-label">Exit Adviser Panel</span>
        </a>
      </nav>

      {{-- User footer --}}
      <div class="sb-user" style="position:relative">
        <div x-data="{ open: false }" style="position:relative">
          <button class="sb-user-btn" @click="if(!collapsed) open = !open">
            <div class="sb-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="sb-user-info">
              <div class="sb-name">{{ auth()->user()->name }}</div>
              <div class="sb-role">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
            <i data-lucide="chevron-up" class="sb-chevron" style="width:12px;height:12px;color:var(--muted2);transition:transform .2s" :style="open ? '' : 'transform:rotate(180deg)'"></i>
          </button>

          <div x-show="open && !collapsed" @click.outside="open = false" x-transition
            class="sb-dropdown">
            <a href="{{ route('profile.edit') }}" class="sb-dd-item"><i data-lucide="user-round"></i> Profile</a>
            <button class="sb-dd-item" onclick="const d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light')">
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
      </div>
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

{{-- Scripts --}}
@include('layouts.partials.scripts')
</body>
</html>