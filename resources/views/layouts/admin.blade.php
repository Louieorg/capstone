<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA Admin</title>
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

    {{-- ══ SIDEBAR (Administrator) ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
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

        {{-- Recommendation Management (routes not built yet — see note below) --}}
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

        {{-- Analytics (routes not built yet — see note below) --}}
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

        {{-- User Management (route not built yet) --}}
        <a href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}"
           class="nav-item {{ request()->routeIs('admin.users.*') ? 'nav-active' : '' }}">
          <i data-lucide="users"></i>
          <span class="nav-label">User Management</span>
        </a>

        <a href="{{ route('admin.category-assignments.index') }}"
   class="nav-item {{ request()->routeIs('admin.category-assignments.*') ? 'nav-active' : '' }}">
  <i data-lucide="git-branch"></i>
  <span class="nav-label">Office Assignments</span>
</a>

        {{-- Settings (route not built yet) --}}
        <a href="{{ Route::has('admin.settings') ? route('admin.settings') : '#' }}"
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