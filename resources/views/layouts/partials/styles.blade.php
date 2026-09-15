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

  /* Status colors — shared across admin pages (Manage Feedback, etc.) */
  html.dark {
    --green: #5fcd8a; --green-bg: rgba(95,205,138,0.10); --green-b: rgba(95,205,138,0.22);
    --red:   #f87171; --red-bg:   rgba(248,113,113,0.10); --red-b:   rgba(248,113,113,0.22);
    --blue:  #60a5fa; --blue-bg:  rgba(96,165,250,0.10);  --blue-b:  rgba(96,165,250,0.20);
  }
  html:not(.dark) {
    --green: #15803d; --green-bg: rgba(22,163,74,0.08);  --green-b: rgba(22,163,74,0.20);
    --red:   #dc2626; --red-bg:   rgba(220,38,38,0.08);  --red-b:   rgba(220,38,38,0.20);
    --blue:  #1d4ed8; --blue-bg:  rgba(29,78,216,0.08);  --blue-b:  rgba(29,78,216,0.18);
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
    display: flex;
    align-items: center;
    text-decoration: none;
    flex-shrink: 0;
}

.h-logo-image {
    height: 44px;
    width: auto;
    object-fit: contain;
    display: block;
}
  .h-logo-text {
    color: var(--text);
    font-family: 'Sora', sans-serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: .06em;
    transition: color .2s, text-shadow .2s;
  }
  html.dark .h-logo-text {
    color: var(--amber);
    text-shadow: 0 0 10px var(--amber-glow), 0 0 22px rgba(251,176,52,.18);
  }
  .h-hamburger {
    width: 34px; height: 34px; border-radius: 9px;
    background: transparent; border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: border-color .15s, background .15s; color: var(--muted);
  }
  .h-hamburger:hover { border-color: var(--amber-mid); color: var(--amber); background: var(--amber-dim); }

  /* Search */
  .h-search { flex: 1; display: flex; justify-content: center; }
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
  .notif-trigger.is-open {
    border-color: var(--amber-mid);
    color: var(--amber);
    background: var(--amber-dim);
  }
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
  .notif-dropdown {
    position: absolute; top: 42px; right: 0;
    width: min(360px, calc(100vw - 32px));
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; overflow: hidden; z-index: 60;
    box-shadow: 0 12px 28px rgba(0,0,0,.28);
  }
  .notif-dropdown-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 14px; border-bottom: 1px solid var(--border); background: var(--surface2);
  }
  .notif-dropdown-title { font-size: 13px; font-weight: 700; color: var(--text); }
  .notif-dropdown-sub { font-size: 11px; color: var(--muted2); }
  .notif-dropdown-list { max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; }
  .notif-item {
    display: flex; gap: 12px; padding: 12px 14px; text-decoration: none; color: inherit;
    transition: background .15s ease; border-bottom: 1px solid var(--border);
  }
  .notif-item:last-child { border-bottom: none; }
  .notif-item:hover { background: var(--amber-dim); }
  .notif-item-icon {
    width: 34px; height: 34px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    background: var(--amber-dim); border: 1px solid var(--amber-mid); color: var(--amber);
  }
  .notif-item-body { min-width: 0; flex: 1; }
  .notif-item-message { font-size: 13px; line-height: 1.5; color: var(--text); }
  .notif-item-meta {
    margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    font-size: 11px; color: var(--muted2);
  }
  .notif-item-chip {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px;
    border: 1px solid var(--amber-mid); background: var(--amber-dim); color: var(--amber); font-weight: 600;
  }
  .notif-empty { padding: 18px 14px; text-align: center; font-size: 12.5px; color: var(--muted); }
  .notif-view-all {
    display: block; padding: 11px 14px; text-align: center; text-decoration: none;
    font-size: 12px; font-weight: 600; color: var(--amber);
    border-top: 1px solid var(--border); background: var(--surface2);
  }
  .notif-view-all:hover { background: var(--amber-dim); }
  .h-btn-ghost {
    padding: 7px 16px; border-radius: 9px; border: 1px solid var(--border); background: transparent;
    color: var(--muted); font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: all .15s;
  }
  .h-btn-ghost:hover { border-color: var(--amber-mid); color: var(--text); }
  .h-btn-amber {
    padding: 7px 16px; border-radius: 9px; background: var(--amber); color: #0a0b0f;
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
    transition: width .25s ease; overflow: hidden;
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
    touch-action: manipulation;
  }
  .nav-item:hover { background: rgba(255,255,255,.05); color: #d0d0dc; }
  .nav-item.nav-active { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
  .nav-item.nav-active::before {
    content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 16px; border-radius: 0 3px 3px 0; background: var(--amber);
  }
  .nav-item i { width: 17px; height: 17px; flex-shrink: 0; }
  .nav-label { transition: opacity .2s; }
  .app-sidebar.collapsed .nav-label { opacity: 0; width: 0; }
  .app-sidebar.collapsed .nav-item { justify-content: center; }

  /* Sub-nav (grouped sections, e.g. admin Feedback Management) */
  .nav-group-btn {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 9px 10px; border-radius: 10px; background: transparent; border: 1px solid transparent;
    font-size: 13px; color: var(--muted); cursor: pointer; font-family: 'DM Sans', sans-serif;
    white-space: nowrap; overflow: hidden; transition: background .15s, color .15s;
  }
  .nav-group-btn:hover { background: rgba(255,255,255,.05); color: #d0d0dc; }
  .nav-group-btn.nav-group-active { color: var(--amber); }
  .nav-group-btn i.chevron { width: 13px; height: 13px; margin-left: auto; transition: transform .2s; flex-shrink: 0; }
  .nav-subitems { display: flex; flex-direction: column; gap: 2px; padding-left: 27px; margin-top: 2px; }
  .app-sidebar.collapsed .nav-subitems { display: none; }
  .app-sidebar.collapsed .nav-group-btn .nav-label,
  .app-sidebar.collapsed .nav-group-btn .chevron { opacity: 0; width: 0; }

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
    background: rgba(0,0,0,.65); backdrop-filter: blur(6px); padding: 16px;
  }
  .modal-card {
    background: var(--surface); border: 1px solid rgba(255,255,255,.09);
    border-radius: 20px; width: 100%; max-width: 400px; overflow: hidden; position: relative;
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
    font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; transition: all .15s;
  }
  .modal-google:hover { background: #20222b; border-color: rgba(255,255,255,.14); color: var(--text); }

  /* ── Bottom Nav ── */
  .bottom-nav {
    position: fixed; bottom: 0; left: 0; right: 0; height: var(--nav-h);
    background: var(--bg2); border-top: 1px solid var(--border);
    display: flex; align-items: stretch; z-index: 200;
    padding-bottom: env(safe-area-inset-bottom); backdrop-filter: blur(16px);
  }
  .bn-item {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 4px;
    text-decoration: none; color: var(--muted); font-size: 10px; font-weight: 500;
    position: relative; transition: color .18s; background: transparent; border: none;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    touch-action: manipulation;
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
    touch-action: manipulation;
  }
  .bn-bubble {
    width: 46px; height: 46px; border-radius: 50%;
    background: linear-gradient(135deg, var(--amber), #f97316);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 16px rgba(251,176,52,.4);
    transition: transform .18s, box-shadow .18s; margin-top: -12px;
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
