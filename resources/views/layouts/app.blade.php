<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }">
<head>
  <title>LIKHA</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('head')

  <script src="https://unpkg.com/lucide@latest"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>
    [x-cloak] { display: none !important; }

    :root {
      --bg:         #f8f8fb;
      --bg2:        #f1f1f5;
      --surface:    #ffffff;
      --surface2:   #f5f5f8;
      --border:     rgba(0,0,0,0.08);
      --amber:      #f59e0b;
      --amber-dim:  rgba(245,158,11,0.10);
      --amber-mid:  rgba(245,158,11,0.20);
      --amber-glow: rgba(245,158,11,0.25);
      --text:       #1a1d24;
      --muted:      #6b7280;
      --muted2:     #b0b8c1;
      --nav-h:      62px;
    }

    html.dark {
      --bg:         #0a0b0f;
      --bg2:        #0e0f14;
      --surface:    #13141a;
      --surface2:   #1a1b23;
      --border:     rgba(255,255,255,0.06);
      --amber:      #fbb034;
      --amber-dim:  rgba(251,176,52,0.10);
      --amber-mid:  rgba(251,176,52,0.22);
      --amber-glow: rgba(251,176,52,0.32);
      --text:       #f0f0f5;
      --muted:      #7e8194;
      --muted2:     #3e4055;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      overflow: hidden;
      height: 100vh;
    }

    /* ── Shell ── */
    .shell { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

    /* ── Header ── */
    .app-header {
      display: flex; align-items: center; gap: 16px;
      padding: 0 20px; height: 58px;
      background: var(--bg2);
      border-bottom: 1px solid var(--border);
      flex-shrink: 0; z-index: 30;
    }
    .h-logo {
      display: flex; align-items: center; gap: 9px;
      text-decoration: none; flex-shrink: 0;
    }
    .h-logo-icon {
      width: 30px; height: 30px; border-radius: 8px;
      background: linear-gradient(135deg, var(--amber), #f07d10);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Sora', sans-serif; font-weight: 800; font-size: 13px; color: #0a0b0f;
      box-shadow: 0 0 14px var(--amber-glow);
    }
    .h-logo-text {
      font-family: 'Sora', sans-serif; font-weight: 700; font-size: 17px;
      letter-spacing: .06em; color: var(--text);
    }
    .h-hamburger {
      width: 34px; height: 34px; border-radius: 9px;
      background: transparent; border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: border-color .15s, background .15s; color: var(--muted);
    }
    .h-hamburger:hover { border-color: var(--amber-mid); color: var(--amber); background: var(--amber-dim); }

    /* Search */
    .h-search {
      flex: 1; display: flex; justify-content: center;
    }
    .h-search form {
      width: 100%; max-width: 480px; display: flex;
      border-radius: 10px; overflow: hidden;
      border: 1px solid var(--border); background: var(--surface);
      transition: border-color .15s, box-shadow .15s;
    }
    .h-search form:focus-within {
      border-color: rgba(251,176,52,.4);
      box-shadow: 0 0 0 3px rgba(251,176,52,.08);
    }
    .h-search input {
      flex: 1; background: transparent; border: none; outline: none;
      padding: 8px 14px; font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
    }
    .h-search input::placeholder { color: var(--muted2); }
    .h-search button {
      padding: 0 14px; background: var(--surface2); border: none;
      border-left: 1px solid var(--border); cursor: pointer;
      display: flex; align-items: center; transition: background .15s;
    }
    .h-search button:hover { background: var(--amber-dim); }

    /* Right side */
    .h-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }
    .h-icon-btn {
      width: 34px; height: 34px; border-radius: 9px;
      border: 1px solid var(--border); background: transparent;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; position: relative; transition: border-color .15s;
      color: var(--muted); text-decoration: none;
    }
    .h-icon-btn:hover { border-color: var(--amber-mid); color: var(--amber); }
    .notif-badge {
      position: absolute; top: -3px; right: -3px;
      min-width: 16px; height: 16px; border-radius: 999px;
      background: #ef4444; color: #fff; font-size: 9px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; padding: 0 3px;
      border: 2px solid var(--bg2);
    }
    .h-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, var(--amber), #f97316);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 13px; color: #0a0b0f; cursor: pointer;
      box-shadow: 0 0 10px var(--amber-glow);
    }
    .h-btn-ghost {
      padding: 7px 16px; border-radius: 9px;
      border: 1px solid var(--border); background: transparent;
      color: var(--muted); font-size: 13px; font-weight: 500;
      cursor: pointer; text-decoration: none; transition: all .15s;
    }
    .h-btn-ghost:hover { border-color: var(--amber-mid); color: var(--text); }
    .h-btn-amber {
      padding: 7px 16px; border-radius: 9px;
      background: var(--amber); color: #0a0b0f;
      font-size: 13px; font-weight: 600; text-decoration: none;
      box-shadow: 0 0 16px var(--amber-glow); transition: all .15s; border: none;
    }
    .h-btn-amber:hover { background: #fcc050; }

    /* ── Body ── */
    .app-body { display: flex; flex: 1; overflow: hidden; }

    /* ── Sidebar ── */
    .app-sidebar {
      width: 220px; flex-shrink: 0;
      background: var(--bg2); border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      transition: width .25s ease;
      overflow: hidden;
    }
    .app-sidebar.collapsed { width: 60px; }

    .sb-nav { flex: 1; padding: 12px 8px; display: flex; flex-direction: column; gap: 2px; overflow-y: auto; }
    .sb-label {
      font-size: 10px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
      color: var(--muted2); padding: 0 10px; margin: 16px 0 6px;
      white-space: nowrap; overflow: hidden;
    }
    .app-sidebar.collapsed .sb-label { opacity: 0; }

    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 10px; border-radius: 10px;
      font-size: 13px; color: var(--muted); text-decoration: none;
      cursor: pointer; position: relative; transition: background .15s, color .15s;
      white-space: nowrap; overflow: hidden; border: 1px solid transparent;
    }
    .nav-item:hover { background: rgba(255,255,255,.05); color: #d0d0dc; }
    .nav-item.nav-active {
      background: var(--amber-dim); color: var(--amber);
      border-color: var(--amber-mid);
    }
    .nav-item.nav-active::before {
      content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
      width: 3px; height: 16px; border-radius: 0 3px 3px 0; background: var(--amber);
    }
    .nav-item i { width: 17px; height: 17px; flex-shrink: 0; }
    .nav-label { transition: opacity .2s; }
    .app-sidebar.collapsed .nav-label { opacity: 0; width: 0; }
    .app-sidebar.collapsed .nav-item { justify-content: center; }

    /* User footer */
    .sb-user { border-top: 1px solid var(--border); padding: 10px 8px; flex-shrink: 0; }
    .sb-user-btn {
      display: flex; align-items: center; gap: 10px; width: 100%;
      padding: 8px 8px; border-radius: 10px; background: transparent; border: none;
      cursor: pointer; text-align: left; overflow: hidden;
      transition: background .15s;
    }
    .sb-user-btn:hover { background: rgba(255,255,255,.05); }
    .sb-avatar {
      width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--amber), #f97316);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 12px; color: #0a0b0f;
    }
    .sb-user-info { flex: 1; min-width: 0; }
    .sb-name { font-size: 12.5px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sb-role { font-size: 11px; color: var(--muted2); }
    .app-sidebar.collapsed .sb-user-info,
    .app-sidebar.collapsed .sb-chevron { display: none; }

    /* User dropdown */
    .sb-dropdown {
      position: absolute; bottom: 58px; left: 8px; right: 8px;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 12px; overflow: hidden; z-index: 50;
      box-shadow: 0 -8px 24px rgba(0,0,0,.4);
    }
    .sb-dd-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; font-size: 13px; color: var(--muted);
      text-decoration: none; cursor: pointer; background: transparent;
      border: none; width: 100%; font-family: 'DM Sans', sans-serif;
      transition: background .15s, color .15s;
    }
    .sb-dd-item:hover { background: rgba(255,255,255,.05); color: var(--text); }
    .sb-dd-item.danger { color: #f87171; }
    .sb-dd-item.danger:hover { background: rgba(239,68,68,.08); }
    .sb-dd-sep { border-top: 1px solid var(--border); }
    .sb-dd-item i { width: 15px; height: 15px; }

    /* ── Main ── */
    .app-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .page-bar {
      padding: 18px 28px; border-bottom: 1px solid var(--border);
      background: var(--bg2); flex-shrink: 0;
      display: flex; align-items: center; justify-content: space-between;
    }
    .page-title { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 700; color: var(--text); }
    .page-sub { font-size: 12.5px; color: var(--muted); margin-top: 3px; }
    .page-content { flex: 1; overflow-y: auto; padding: 28px; }

    /* ── Login Modal ── */
    .modal-backdrop {
      position: fixed; inset: 0; z-index: 50;
      display: flex; align-items: center; justify-content: center;
      background: rgba(0,0,0,.65); backdrop-filter: blur(6px);
      padding: 16px;
    }
    .modal-card {
      background: var(--surface); border: 1px solid rgba(255,255,255,.09);
      border-radius: 20px; width: 100%; max-width: 400px;
      overflow: hidden; position: relative;
    }
    .modal-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg, transparent, rgba(251,176,52,.5), transparent);
    }
    .modal-body { padding: 32px 28px; }
    .modal-close {
      position: absolute; top: 14px; right: 14px;
      width: 28px; height: 28px; border-radius: 8px;
      background: transparent; border: 1px solid var(--border);
      color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .modal-close:hover { border-color: var(--amber-mid); color: var(--text); }
    .modal-input {
      width: 100%; background: var(--surface2); border: 1px solid var(--border);
      border-radius: 10px; padding: 10px 14px; font-size: 13px;
      color: var(--text); font-family: 'DM Sans', sans-serif; outline: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .modal-input::placeholder { color: var(--muted2); }
    .modal-input:focus { border-color: rgba(251,176,52,.45); box-shadow: 0 0 0 3px rgba(251,176,52,.08); }
    .modal-btn {
      width: 100%; background: var(--amber); color: #0a0b0f;
      border: none; border-radius: 10px; padding: 11px;
      font-size: 13.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
      cursor: pointer; box-shadow: 0 0 20px rgba(251,176,52,.28); transition: all .18s;
    }
    .modal-btn:hover { background: #fcc050; }
    .modal-google {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; background: var(--surface2); border: 1px solid var(--border);
      border-radius: 10px; padding: 10px; font-size: 13px; color: var(--muted);
      font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none;
      transition: all .15s;
    }
    .modal-google:hover { background: #20222b; border-color: rgba(255,255,255,.14); color: var(--text); }

    /* ── Bottom Nav ── */
    .bottom-nav {
      position: fixed; bottom: 0; left: 0; right: 0; height: var(--nav-h);
      background: var(--bg2); border-top: 1px solid var(--border);
      display: flex; align-items: stretch; z-index: 200;
      padding-bottom: env(safe-area-inset-bottom);
      backdrop-filter: blur(16px);
    }
    .bn-item {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center; gap: 4px;
      text-decoration: none; color: var(--muted); font-size: 10px; font-weight: 500;
      position: relative; transition: color .18s; background: transparent; border: none;
      font-family: 'DM Sans', sans-serif; cursor: pointer;
    }
    .bn-item:active { transform: scale(.92); }
    .bn-item.bn-active { color: var(--amber); }
    .bn-item.bn-active::after {
      content: ''; position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
      width: 4px; height: 4px; border-radius: 50%; background: var(--amber);
      box-shadow: 0 0 6px rgba(251,176,52,.7);
    }
    .bn-center {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center; gap: 3px;
      text-decoration: none; color: var(--muted2); font-size: 10px; font-weight: 600;
    }
    .bn-bubble {
      width: 46px; height: 46px; border-radius: 50%;
      background: linear-gradient(135deg, var(--amber), #f97316);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 16px rgba(251,176,52,.4);
      transition: transform .18s, box-shadow .18s;
      margin-top: -12px;
    }
    .bn-center:active .bn-bubble { transform: scale(.9); }
    .bn-item i, .bn-center i { width: 19px; height: 19px; }

    @media (max-width: 767px) {
      .app-sidebar { display: none; }
      .app-main { padding-bottom: var(--nav-h); }
      .h-hamburger { display: none; }
    }
    @media (min-width: 768px) {
      .bottom-nav { display: none; }
    }
  </style>
</head>

<body
  x-data="{
    loginOpen: false,
    userOpen: false,
    collapsed: localStorage.getItem('sidebar') === 'collapsed'
  }"
  x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? 'collapsed' : 'expanded'))">

<div class="shell">

  {{-- ══ HEADER ══ --}}
  <header class="app-header">

    {{-- Hamburger --}}
    <button @click="collapsed = !collapsed" class="h-hamburger hidden md:flex">
      <i data-lucide="menu" style="width:17px;height:17px;"></i>
    </button>

    {{-- Logo --}}
    <a href="{{ route('home') }}" class="h-logo">
      <div class="h-logo-icon">L</div>
      <span class="h-logo-text">LIKHA</span>
    </a>

    {{-- Search --}}
    <div class="h-search">
      <form method="GET" action="{{ route('feedback.index') }}">
        <input type="text" name="search" placeholder="Search campus problems…" value="{{ request('search') }}">
        <button type="submit">
          <i data-lucide="search" style="width:14px;height:14px;color:#7e8194;"></i>
        </button>
      </form>
    </div>

    {{-- Right --}}
    <div class="h-right">
      @auth
        <a href="/notifications" class="h-icon-btn">
          <i data-lucide="bell" style="width:16px;height:16px;"></i>
          @if(auth()->user()->unreadNotifications->count())
            <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
          @endif
        </a>

        <div x-data="{ open: false }" class="relative">
          <div class="h-avatar" @click="open = !open">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
          </div>
          <div x-show="open" @click.outside="open = false" x-transition
            style="position:absolute;top:42px;right:0;width:180px;background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;z-index:50;box-shadow:0 8px 24px rgba(0,0,0,.4)">
            <div style="padding:10px 14px;border-bottom:1px solid var(--border)">
              <div style="font-size:13px;font-weight:500;color:var(--text)">{{ auth()->user()->name }}</div>
              <div style="font-size:11px;color:var(--muted2)">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
            <a href="/settings" class="sb-dd-item"><i data-lucide="settings"></i> Settings</a>
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

  <div class="app-body">

    {{-- ══ SIDEBAR ══ --}}
    <aside class="app-sidebar" :class="{ collapsed }" x-show="true">
      <nav class="sb-nav">
        <div class="sb-label">Main</div>

        <a href="{{ route('home') }}"
           class="nav-item {{ request()->routeIs('home') ? 'nav-active' : '' }}">
          <i data-lucide="home"></i>
          <span class="nav-label">Home</span>
        </a>

        <a href="{{ route('feedback.create') }}"
           class="nav-item {{ request()->routeIs('feedback.create') ? 'nav-active' : '' }}">
          <i data-lucide="plus-circle"></i>
          <span class="nav-label">Submit Problem</span>
        </a>

        <a href="{{ route('feedback.index') }}"
           class="nav-item {{ request()->routeIs('feedback.index') ? 'nav-active' : '' }}">
          <i data-lucide="list"></i>
          <span class="nav-label">Problems</span>
        </a>

        <a href="{{ route('feedback.summary') }}"
           class="nav-item {{ request()->routeIs('feedback.summary') ? 'nav-active' : '' }}">
          <i data-lucide="bar-chart-3"></i>
          <span class="nav-label">Category Summary</span>
        </a>

        @auth
          <a href="/my-ideas"
             class="nav-item {{ request()->is('my-ideas') ? 'nav-active' : '' }}">
            <i data-lucide="bookmark"></i>
            <span class="nav-label">Saved Ideas</span>
          </a>

          @if(auth()->user()->role === 'adviser')
            <div class="sb-label">Adviser</div>
            <a href="{{ route('adviser.dashboard') }}"
               class="nav-item {{ request()->is('adviser/dashboard') ? 'nav-active' : '' }}">
              <i data-lucide="clipboard-check"></i>
              <span class="nav-label">My Dashboard</span>
            </a>
          @endif

          @if(auth()->user()->role === 'admin')
            <div class="sb-label">Admin</div>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ request()->is('admin/dashboard') ? 'nav-active' : '' }}">
              <i data-lucide="shield"></i>
              <span class="nav-label">Admin Dashboard</span>
            </a>
          @endif
        @endauth
      </nav>

      {{-- User footer --}}
      @auth
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
            <a href="/settings" class="sb-dd-item"><i data-lucide="settings"></i> Settings</a>
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
      @endauth
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
  <a href="{{ route('home') }}" class="bn-item {{ request()->routeIs('home') ? 'bn-active' : '' }}">
    <i data-lucide="home"></i><span>Home</span>
  </a>
  <a href="{{ route('feedback.index') }}" class="bn-item {{ request()->routeIs('feedback.index') ? 'bn-active' : '' }}">
    <i data-lucide="list"></i><span>Problems</span>
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
  @endguest
</nav>

{{-- ══ LOGIN MODAL ══ --}}
<div x-show="loginOpen"
     @keydown.escape.window="loginOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-backdrop" x-cloak>

  <div @click.outside="loginOpen = false" class="modal-card"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100">

    <button @click="loginOpen = false" class="modal-close">
      <i data-lucide="x" style="width:14px;height:14px;"></i>
    </button>

    <div class="modal-body">
      {{-- Logo --}}
      <div style="text-align:center;margin-bottom:24px">
        <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:8px">
          <div class="h-logo-icon">L</div>
          <span style="font-family:'Sora',sans-serif;font-weight:700;font-size:18px;letter-spacing:.06em;color:var(--text)">LIKHA</span>
        </div>
        <div style="font-size:12.5px;color:var(--muted)">Sign in to your account</div>
      </div>

      <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:12px">
        @csrf
        <input type="email" name="email" class="modal-input" placeholder="Email address"
          x-ref="loginEmail"
          x-init="$watch('loginOpen', v => v && $nextTick(() => $refs.loginEmail.focus()))"
          required>
        <input type="password" name="password" class="modal-input" placeholder="Password" required>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px">
          <label style="display:flex;align-items:center;gap:6px;color:var(--muted);cursor:pointer">
            <input type="checkbox" name="remember" style="accent-color:var(--amber)"> Remember me
          </label>
          <a href="{{ route('password.request') }}" style="color:var(--amber);text-decoration:none;font-size:12px">Forgot password?</a>
        </div>

        <button type="submit" class="modal-btn">Sign in</button>
      </form>

      <div style="display:flex;align-items:center;gap:12px;margin:16px 0">
        <div style="flex:1;height:1px;background:var(--border)"></div>
        <span style="font-size:11px;color:var(--muted2)">OR</span>
        <div style="flex:1;height:1px;background:var(--border)"></div>
      </div>

      <a href="{{ route('google.login') }}" class="modal-google">
        <img src="https://developers.google.com/identity/images/g-logo.png" style="width:16px;height:16px;">
        Continue with Google
      </a>

      <p style="text-align:center;font-size:12px;color:var(--muted2);margin-top:18px">
        Don't have an account?
        <a href="{{ route('register') }}" style="color:var(--amber);text-decoration:none;font-weight:500">Register</a>
      </p>
    </div>
  </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Initialize theme on page load
  if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }

  @if(session('success'))
  Swal.fire({
    toast: true, position: 'top-end', icon: 'success',
    title: "{{ session('success') }}",
    showConfirmButton: false, timer: 3000, timerProgressBar: true,
    background: '#13141a', color: '#f0f0f5',
    iconColor: '#fbb034',
  });
  @endif

  @if(session('info'))
  Swal.fire({
    toast: true, position: 'top-end', icon: 'info',
    title: "{{ session('info') }}",
    showConfirmButton: false, timer: 3500, timerProgressBar: true,
    background: '#13141a', color: '#f0f0f5',
    iconColor: '#60a5fa',
  });
  @endif

  @if(session('showLogin'))
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[x-data]').__x.$data.loginOpen = true;
  });
  @endif

  function confirmLogout() {
    Swal.fire({
      title: 'Logging out?',
      text: 'You will be signed out of LIKHA.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#fbb034',
      cancelButtonColor: '#3e4055',
      confirmButtonText: 'Yes, sign out',
      background: '#13141a',
      color: '#f0f0f5',
    }).then(r => { if (r.isConfirmed) document.getElementById('logoutForm').submit(); });
  }
</script>
<script>lucide.createIcons();</script>
@stack('scripts')
</body>
</html>
