<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA Office Review</title>
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
  x-data="{ collapsed: localStorage.getItem('sidebar') === 'collapsed' }"
  x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? 'collapsed' : 'expanded'))">

<div class="shell">

  @include('layouts.partials.header')

  <div class="app-body">

    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
      <nav class="sb-nav">
        <div class="sb-label">{{ auth()->user()->officeLabel() ?? 'Office' }}</div>

        @if(auth()->user()->isOfficeReviewer())
          <a href="{{ route('office.review.index') }}"
             class="nav-item {{ request()->routeIs('office.review.*') ? 'nav-active' : '' }}">
            <i data-lucide="clipboard-check"></i>
            <span class="nav-label">Review Queue</span>
          </a>
        @endif

        {{-- The queue belongs to the office's own representative, so it is only
             offered to a user who currently represents an active office. A
             reviewer role alone does not grant it. --}}
        @if(auth()->user()->isOfficeRepresentative())
          <a href="{{ route('office.confirmations.index') }}"
             class="nav-item {{ request()->routeIs('office.confirmations.*') ? 'nav-active' : '' }}">
            <i data-lucide="badge-check"></i>
            <span class="nav-label">Confirmation Requests</span>
          </a>
        @endif

        <div class="sb-label">Back to LIKHA</div>
        <a href="{{ route('home') }}" class="nav-item">
          <i data-lucide="arrow-left"></i>
          <span class="nav-label">Exit Review Panel</span>
        </a>
      </nav>

    </aside>

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
<nav class="bottom-nav">
  @if(auth()->user()->isOfficeReviewer())
    <a href="{{ route('office.review.index') }}"
       class="bn-item {{ request()->routeIs('office.review.*') ? 'bn-active' : '' }}">
      <i data-lucide="clipboard-check"></i><span>Review Queue</span>
    </a>
  @endif
  @if(auth()->user()->isOfficeRepresentative())
    <a href="{{ route('office.confirmations.index') }}"
       class="bn-item {{ request()->routeIs('office.confirmations.*') ? 'bn-active' : '' }}">
      <i data-lucide="badge-check"></i><span>Confirmations</span>
    </a>
  @endif
  <a href="{{ route('home') }}" class="bn-item">
    <i data-lucide="arrow-left"></i><span>Exit Review</span>
  </a>
</nav>

@include('layouts.partials.scripts')
</body>
</html>