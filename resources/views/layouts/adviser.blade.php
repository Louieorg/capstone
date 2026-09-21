<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA Adviser</title>
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