<!DOCTYPE html>
<html lang="en"
x-data="{
    darkMode: {{ auth()->check() ? 'true' : 'false' }}
}"
x-init="
    if(darkMode) {
        const saved = localStorage.getItem('theme');
        if(saved === 'dark') {
            document.documentElement.classList.add('dark');
        }
    } else {
        document.documentElement.classList.remove('dark');
    }
"
>

<head>
<title>LIKHA</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
[x-cloak] { display: none !important; }

:root {
  --amber: #fbb034;
  --amber-dim: rgba(251,176,52,0.10);
  --amber-border: rgba(251,176,52,0.22);
  --amber-glow: 0 0 14px rgba(251,176,52,0.28);
  --nav-h: 64px; /* bottom nav height on mobile */
}

@import url('https://fonts.googleapis.com/css2?family=Sora:wght@600;700&display=swap');

.nav-active { background: var(--amber-dim); color: #fbb034; box-shadow: var(--amber-glow); }
.nav-active i { color: #fbb034 !important; }

.nav-item {
  display: flex; align-items: center;
  padding: 9px 14px; border-radius: 10px;
  font-size: 13.5px; font-weight: 400; color: #9ca3af;
  transition: background .18s, color .18s, transform .18s;
  text-decoration: none; position: relative;
  margin-bottom: 2px;
}
.nav-item:hover { background: rgba(255,255,255,0.05); color: #e5e7eb; transform: translateX(2px); }
.nav-item.nav-active { color: #fbb034; transform: translateX(2px); }
.nav-item.nav-active::before {
  content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  width: 3px; height: 16px; border-radius: 0 3px 3px 0; background: var(--amber);
}

.search-wrap:focus-within { box-shadow: 0 0 0 2px rgba(251,176,52,0.35); }

.modal-input {
  width: 100%; border: 1px solid #e5e7eb; border-radius: 10px;
  padding: 10px 14px; font-size: 13.5px; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.modal-input:focus { border-color: var(--amber); box-shadow: 0 0 0 3px var(--amber-dim); }

@keyframes badge-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }
.notif-badge { animation: badge-pulse 2s ease-in-out infinite; }

/* ══════════════════════════════════════
   BOTTOM NAV — mobile only
   ══════════════════════════════════════ */
.bottom-nav {
  position: fixed; bottom: 0; left: 0; right: 0;
  height: var(--nav-h);
  background: linear-gradient(to top, #0c0d12, #13141a);
  border-top: 1px solid rgba(255,255,255,0.06);
  display: flex; align-items: stretch;
  z-index: 200;
  padding-bottom: env(safe-area-inset-bottom); /* iPhone notch support */
  backdrop-filter: blur(12px);
}

.bn-item {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4px; text-decoration: none;
  color: #6b7280; font-size: 10px; font-weight: 500;
  position: relative; transition: color .18s;
  -webkit-tap-highlight-color: transparent;
}
.bn-item:active { transform: scale(.92); }
.bn-item.bn-active { color: var(--amber); }

/* Active dot indicator */
.bn-item.bn-active::after {
  content: '';
  position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%);
  width: 4px; height: 4px; border-radius: 50%;
  background: var(--amber);
  box-shadow: 0 0 6px rgba(251,176,52,0.7);
}

/* Center action button (Submit) */
.bn-center {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 3px; text-decoration: none;
  color: #0e0f14; font-size: 10px; font-weight: 600;
  position: relative; -webkit-tap-highlight-color: transparent;
}
.bn-center-bubble {
  width: 48px; height: 48px; border-radius: 50%;
  background: linear-gradient(135deg, #fbb034, #f97316);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(251,176,52,0.45);
  transition: transform .18s, box-shadow .18s;
  margin-top: -14px; /* lifts it above the nav */
}
.bn-center:active .bn-center-bubble {
  transform: scale(.9);
  box-shadow: 0 2px 8px rgba(251,176,52,0.3);
}

/* Icon size inside bottom nav */
.bn-item i, .bn-center i { width: 20px; height: 20px; }

/* Scroll padding so content isn't hidden behind bottom nav */
@media (max-width: 767px) {
  .main-scroll { padding-bottom: calc(var(--nav-h) + 16px); }
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet"/>
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('head')

<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body
x-data="{
    loginOpen: false,
    open: false,
    collapsed: localStorage.getItem('sidebar') === 'collapsed'
}"
x-init="
    $watch('collapsed', value => {
        localStorage.setItem('sidebar', value ? 'collapsed' : 'expanded')
    })
"
class="bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-gray-200 font-[Montserrat]">

<div class="h-screen flex flex-col overflow-hidden">

{{-- ══ HEADER ══ --}}
<header class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 px-5 py-3 flex items-center gap-4 border-b border-white/5 z-30">

  {{-- LEFT: hamburger + logo --}}
  <div class="flex items-center gap-3 w-1/3">
    {{-- Hamburger — desktop only (mobile uses bottom nav) --}}
    <button
      @click="collapsed = !collapsed"
      class="hidden md:flex p-2 rounded-lg hover:bg-white/10 transition text-gray-400 hover:text-amber-400">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <a href="{{ route('home') }}" class="flex items-center gap-2 no-underline">
      <svg class="w-7 h-7 text-amber-400" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M32 6C20 6 12 14 12 26c0 8 5 12 8 16v4h24v-4c3-4 8-8 8-16 0-12-8-20-20-20z" stroke="currentColor" stroke-width="3"/>
        <rect x="22" y="28" width="3" height="8" fill="currentColor"/>
        <rect x="28" y="24" width="3" height="12" fill="currentColor"/>
        <rect x="34" y="26" width="3" height="10" fill="currentColor"/>
        <path d="M24 22l4 4 8-8" stroke="currentColor" stroke-width="2" fill="none"/>
        <path d="M22 34c6-6 12-10 18-12" stroke="currentColor" stroke-width="2"/>
        <path d="M40 22l-2-2 6-2-2 6-2-2" fill="currentColor"/>
      </svg>
      <span class="text-lg font-bold tracking-widest text-amber-400" style="font-family:'Sora',sans-serif;">LIKHA</span>
    </a>
  </div>

  {{-- CENTER: search --}}
  <div class="flex-1 flex justify-center">
    <form method="GET" action="{{ route('feedback.index') }}" class="w-full max-w-lg">
      <div class="search-wrap flex rounded-full overflow-hidden border border-slate-700 bg-slate-800/60 transition-all duration-200 focus-within:border-amber-400">
        <input type="text" name="search" placeholder="Search campus problems…"
          class="w-full px-4 py-2 text-sm bg-transparent text-gray-200 placeholder-gray-500 outline-none"/>
        <button class="px-4 flex items-center border-l border-slate-700 bg-slate-700 hover:bg-amber-500 transition-all duration-200 group">
          <i data-lucide="search" class="w-4 h-4 text-gray-400 group-hover:text-black transition"></i>
        </button>
      </div>
    </form>
  </div>

  {{-- RIGHT: auth --}}
  <div class="w-1/3 flex justify-end items-center gap-3 text-sm">
    @auth
    <a href="/notifications" class="relative p-2 rounded-lg hover:bg-white/10 transition text-gray-400 hover:text-amber-400">
      <i data-lucide="bell" class="w-5 h-5"></i>
      @if(auth()->user()->unreadNotifications->count())
      <span class="notif-badge absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[17px] h-[17px] flex items-center justify-center px-1">
        {{ auth()->user()->unreadNotifications->count() }}
      </span>
      @endif
    </a>
    @endauth

    @guest
    <button @click="loginOpen = true"
      class="px-4 py-1.5 rounded-lg text-sm font-medium text-gray-300 border border-slate-600 hover:border-amber-400/50 hover:text-amber-400 transition">
      Login
    </button>
    <a href="{{ route('register') }}"
      class="hidden sm:inline-block px-4 py-1.5 rounded-lg text-sm font-semibold bg-amber-400 text-black hover:bg-amber-300 transition shadow-[0_0_12px_rgba(251,176,52,0.3)]">
      Register
    </a>
    @endguest
  </div>

</header>

{{-- Desktop sidebar backdrop --}}
<div x-show="open" x-transition class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden" @click="open=false"></div>

<div class="flex flex-1 overflow-hidden">

  {{-- ══ SIDEBAR — desktop only ══ --}}
  <aside
    :class="[collapsed ? 'w-[68px]' : 'w-60']"
    class="hidden md:flex bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border-r border-white/5 flex-col shrink-0 transition-all duration-300">

    <nav class="flex-1 px-3 py-5 space-y-1">
      <p x-show="!collapsed" x-cloak class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600 px-3 mb-3">Main</p>

      <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="home" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Home</span>
      </a>

      <a href="{{ route('feedback.create') }}" class="nav-item {{ request()->routeIs('feedback.create') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="plus-circle" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Submit Problem</span>
      </a>

      <a href="{{ route('feedback.index') }}" class="nav-item {{ request()->routeIs('feedback.index') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="list" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Problems</span>
      </a>

      <a href="{{ route('feedback.summary') }}" class="nav-item {{ request()->routeIs('feedback.summary') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="bar-chart-3" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Category Summary</span>
      </a>

      @auth
      <a href="/my-ideas" class="nav-item {{ request()->is('my-ideas') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="bookmark" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Saved Ideas</span>
      </a>
      @endauth

      @auth
@if(auth()->user()->role === 'adviser')
<a href="{{ route('adviser.dashboard') }}"
   class="nav-item {{ request()->is('adviser/dashboard') ? 'nav-active' : '' }}"
   :class="collapsed ? 'justify-center' : 'gap-3'">
  
  <i data-lucide="clipboard-check"
     class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>

  <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">
    My Dashboard
  </span>

</a>
@endif
@endauth

      @auth
      @if(auth()->user()->role === 'admin')
      <div x-show="!collapsed" x-cloak class="pt-4">
        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600 px-3 mb-2">Admin</p>
      </div>
      <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->is('admin/dashboard') ? 'nav-active' : '' }}" :class="collapsed ? 'justify-center' : 'gap-3'">
        <i data-lucide="shield" class="w-[18px] h-[18px] shrink-0 text-gray-500 transition"></i>
        <span x-show="!collapsed" x-cloak x-transition.opacity class="truncate">Admin Dashboard</span>
      </a>
      @endif
      @endauth
    </nav>

    {{-- User account --}}
    <div class="border-t border-white/5 p-3">
      @auth
      <div x-data="{ open: false }" class="relative">
        <button @click="if (!collapsed) open = !open"
          class="flex items-center w-full text-left rounded-xl p-2.5 hover:bg-white/5 transition-all duration-200"
          :class="collapsed ? 'justify-center' : 'gap-3'">
          <div class="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center text-black font-bold text-sm shadow-md shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
          </div>
          <div x-show="!collapsed" x-cloak class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
          </div>
          <i x-show="!collapsed" x-cloak data-lucide="chevron-up" class="w-3.5 h-3.5 text-gray-500 shrink-0" :class="open ? '' : 'rotate-180'" style="transition:transform .2s"></i>
        </button>

        <div x-show="open && !collapsed" @click.outside="open = false" x-transition
          class="absolute bottom-14 left-0 w-full bg-slate-900 border border-slate-700 rounded-xl shadow-xl overflow-hidden z-10">
          <a href="/settings" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-300 hover:bg-slate-800 transition">
            <i data-lucide="settings" class="w-4 h-4 text-gray-500"></i> Settings
          </a>
          <button
            @click="
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
"
            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-300 hover:bg-slate-800 transition">
            <i data-lucide="sun" class="w-4 h-4 text-gray-500"></i> Toggle Dark Mode
          </button>
          <div class="border-t border-slate-700"></div>
          <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <button type="button" onclick="confirmLogout()"
              class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-400 hover:bg-slate-800 transition">
              <i data-lucide="log-out" class="w-4 h-4"></i> Logout
            </button>
          </form>
        </div>
      </div>
      @endauth
    </div>

  </aside>

  {{-- ══ MAIN ══ --}}
  <div class="flex-1 flex flex-col overflow-hidden">
    <main class="flex-1 overflow-y-auto main-scroll">

      @hasSection('title')
      <div class="bg-white dark:bg-slate-950 border-b border-gray-200 dark:border-white/5 px-6 md:px-10 py-5">
        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100" style="font-family:'Sora',sans-serif;">
          @yield('title')
        </h1>
        @hasSection('subtitle')
        <p class="text-sm text-gray-500 mt-1">@yield('subtitle')</p>
        @endif
      </div>
      @endif

      <div class="p-6 md:p-10 transition-all duration-300">
        @yield('content')
      </div>

    </main>
  </div>

</div>
</div>

{{-- ══════════════════════════════════════════════
     BOTTOM NAV — mobile only (hidden on md+)
     ══════════════════════════════════════════════ --}}
<nav class="bottom-nav md:hidden">

  {{-- Home --}}
  <a href="{{ route('home') }}"
     class="bn-item {{ request()->routeIs('home') ? 'bn-active' : '' }}">
    <i data-lucide="home"></i>
    <span>Home</span>
  </a>

  {{-- Problems --}}
  <a href="{{ route('feedback.index') }}"
     class="bn-item {{ request()->routeIs('feedback.index') ? 'bn-active' : '' }}">
    <i data-lucide="list"></i>
    <span>Problems</span>
  </a>

  {{-- Submit — center bubble --}}
  <a href="{{ route('feedback.create') }}" class="bn-center">
    <div class="bn-center-bubble">
      <i data-lucide="plus" class="w-6 h-6 text-black"></i>
    </div>
    <span style="color:#9ca3af; margin-top:2px;">Submit</span>
  </a>

  {{-- Summary --}}
  <a href="{{ route('feedback.summary') }}"
     class="bn-item {{ request()->routeIs('feedback.summary') ? 'bn-active' : '' }}">
    <i data-lucide="bar-chart-3"></i>
    <span>Summary</span>
  </a>

  {{-- Account / Saved --}}
  @auth
  <a href="/my-ideas"
     class="bn-item {{ request()->is('my-ideas') ? 'bn-active' : '' }}">
    <i data-lucide="bookmark"></i>
    <span>Saved</span>
  </a>
  @else
  {{-- Guest: show login trigger --}}
  <button @click="loginOpen = true" class="bn-item">
    <i data-lucide="user"></i>
    <span>Login</span>
  </button>
  @endauth

</nav>

{{-- ══ LOGIN MODAL ══ --}}
<div x-show="loginOpen"
     @keydown.escape.window="loginOpen = false"
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-180"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">

  <div @click.outside="loginOpen = false"
       class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden relative border border-gray-100 dark:border-slate-700">

    <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500"></div>

    <div class="p-7">
      <button @click="loginOpen = false"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>

      <div class="flex flex-col items-center mb-6">
        <svg class="w-8 h-8 text-amber-400 mb-2" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M32 6C20 6 12 14 12 26c0 8 5 12 8 16v4h24v-4c3-4 8-8 8-16 0-12-8-20-20-20z" stroke="currentColor" stroke-width="3"/>
          <rect x="22" y="28" width="3" height="8" fill="currentColor"/>
          <rect x="28" y="24" width="3" height="12" fill="currentColor"/>
          <rect x="34" y="26" width="3" height="10" fill="currentColor"/>
          <path d="M24 22l4 4 8-8" stroke="currentColor" stroke-width="2" fill="none"/>
          <path d="M22 34c6-6 12-10 18-12" stroke="currentColor" stroke-width="2"/>
          <path d="M40 22l-2-2 6-2-2 6-2-2" fill="currentColor"/>
        </svg>
        <span class="text-xl font-bold tracking-widest text-amber-400" style="font-family:'Sora',sans-serif;">LIKHA</span>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Sign in to your account</p>
      </div>

      <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <input type="email" name="email"
          x-ref="email"
          x-init="$watch('loginOpen', val => val && $nextTick(() => $refs.email.focus()))"
          placeholder="Email address"
          class="modal-input dark:bg-slate-800 dark:border-slate-600 dark:text-gray-200 dark:placeholder-gray-500"
          required/>
        <input type="password" name="password" placeholder="Password"
          class="modal-input dark:bg-slate-800 dark:border-slate-600 dark:text-gray-200 dark:placeholder-gray-500"
          required/>

        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400"/>
            Remember me
          </label>
          <a href="{{ route('password.request') }}" class="text-amber-500 hover:text-amber-400 hover:underline transition">
            Forgot password?
          </a>
        </div>

        <button class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-black font-semibold text-sm transition shadow-[0_4px_14px_rgba(251,176,52,0.35)]">
          Sign In
        </button>
      </form>

      <div class="flex items-center my-5 gap-3">
        <div class="flex-1 border-t border-gray-200 dark:border-slate-700"></div>
        <span class="text-xs text-gray-400 font-medium">OR</span>
        <div class="flex-1 border-t border-gray-200 dark:border-slate-700"></div>
      </div>

      <a href="{{ route('google.login') }}"
        class="flex items-center justify-center gap-2.5 border border-gray-200 dark:border-slate-600 rounded-xl py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
        <img src="https://developers.google.com/identity/images/g-logo.png" class="w-4 h-4"/>
        Continue with Google
      </a>

      <p class="text-center text-xs text-gray-400 mt-5">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-amber-500 hover:underline font-medium">Register</a>
      </p>
    </div>
  </div>

  
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
@if(session('success'))
Swal.fire({
  toast: true, position: 'top-end', icon: 'success',
  title: "{{ session('success') }}",
  showConfirmButton: false, timer: 3000, timerProgressBar: true
});
@endif
</script>

<script>
function confirmLogout() {
  Swal.fire({
    title: 'Logging out?',
    text: 'You will be signed out of LIKHA.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#fbb034',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, sign out',
    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff',
    color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
  }).then(result => {
    if (result.isConfirmed) document.getElementById('logoutForm').submit();
  });
}
</script>

<script>lucide.createIcons();</script>

@if(session('showLogin'))
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelector('body').__x.$data.loginOpen = true;
});
</script>
@endif

<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
</script>

</body>
</html>