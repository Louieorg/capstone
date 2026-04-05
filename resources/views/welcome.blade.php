<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>LIKHA</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
<script src="https://unpkg.com/lucide@latest"></script>
<style>

/* ═══════════════════════════════════════
   TOKENS
═══════════════════════════════════════ */
:root {
  --bg:        #0a0b0f;
  --bg2:       #0e0f14;
  --surface:   #13141a;
  --surface2:  #1a1b23;
  --border:    rgba(255,255,255,0.06);
  --amber:     #fbb034;
  --amber-dim: rgba(251,176,52,0.10);
  --amber-mid: rgba(251,176,52,0.22);
  --amber-glow:rgba(251,176,52,0.35);
  --text:      #f0f0f5;
  --muted:     #7e8194;
  --muted2:    #3e4055;
  --r:         16px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  overflow-x: hidden;
  cursor: none; /* custom cursor */
}

/* ── Custom cursor ── */
.cursor {
  position: fixed; width: 10px; height: 10px; border-radius: 50%;
  background: var(--amber); pointer-events: none; z-index: 9999;
  transform: translate(-50%,-50%);
  transition: transform .1s, width .25s, height .25s, opacity .25s;
  mix-blend-mode: screen;
}
.cursor-ring {
  position: fixed; width: 36px; height: 36px; border-radius: 50%;
  border: 1px solid rgba(251,176,52,.4); pointer-events: none; z-index: 9998;
  transform: translate(-50%,-50%);
  transition: transform .18s ease-out, width .25s, height .25s;
}
body:hover .cursor { opacity: 1; }

/* ── Grain overlay ── */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 9990; pointer-events: none;
  opacity: .025;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
  background-repeat: repeat;
  background-size: 128px;
}

/* ═══════════════════════════════════════
   NAV
═══════════════════════════════════════ */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 48px;
  background: linear-gradient(to bottom, rgba(10,11,15,0.95), transparent);
  backdrop-filter: blur(0px);
  transition: backdrop-filter .3s, background .3s;
}
nav.scrolled {
  background: rgba(10,11,15,0.92);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
}
.nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.nav-logo-icon {
  width: 32px; height: 32px; border-radius: 9px;
  background: linear-gradient(135deg, var(--amber), #f07d10);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Sora', sans-serif; font-weight: 800; font-size: 14px; color: #0a0b0f;
  box-shadow: 0 0 20px var(--amber-glow);
}
.nav-logo-text { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 18px; letter-spacing: .06em; color: var(--text); }
.nav-links { display: flex; align-items: center; gap: 32px; }
.nav-links a { font-size: 13.5px; color: var(--muted); text-decoration: none; transition: color .2s; }
.nav-links a:hover { color: var(--text); }
.nav-cta {
  display: flex; align-items: center; gap: 10px;
}
.btn-ghost-sm {
  padding: 8px 18px; border-radius: 9px; font-size: 13px; font-weight: 500;
  background: transparent; border: 1px solid rgba(255,255,255,.12);
  color: var(--muted); cursor: pointer; text-decoration: none;
  transition: all .18s;
}
.btn-ghost-sm:hover { border-color: var(--amber-mid); color: var(--text); }
.btn-amber {
  padding: 8px 20px; border-radius: 9px; font-size: 13px; font-weight: 600;
  background: var(--amber); color: #0a0b0f; cursor: pointer;
  text-decoration: none; border: none;
  box-shadow: 0 0 20px var(--amber-glow);
  transition: all .18s;
}
.btn-amber:hover { background: #fcc050; box-shadow: 0 0 28px var(--amber-glow); transform: translateY(-1px); }

/* ═══════════════════════════════════════
   HERO
═══════════════════════════════════════ */
.hero {
  min-height: 100vh;
  display: flex; flex-direction: column; justify-content: center;
  padding: 60px 48px 80px;
  position: relative; overflow: hidden;
}

/* Ambient orbs */
.hero::before {
  content: '';
  position: absolute; top: -120px; left: -120px;
  width: 600px; height: 600px; border-radius: 50%;
  background: radial-gradient(circle, rgba(251,176,52,.08) 0%, transparent 70%);
  pointer-events: none;
}
.hero::after {
  content: '';
  position: absolute; bottom: -80px; right: -80px;
  width: 500px; height: 500px; border-radius: 50%;
  background: radial-gradient(circle, rgba(249,115,22,.06) 0%, transparent 70%);
  pointer-events: none;
}

/* Floating grid lines */
.hero-grid {
  position: absolute; inset: 0; pointer-events: none; overflow: hidden;
  opacity: .035;
}
.hero-grid svg { width: 100%; height: 100%; }

.hero-inner { max-width: 1100px; margin: 0 auto; width: 100%; position: relative; z-index: 2; }

/* Eyebrow */
.eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 999px;
  background: var(--amber-dim); border: 1px solid var(--amber-mid);
  font-size: 11px; font-weight: 600; letter-spacing: .1em;
  text-transform: uppercase; color: var(--amber);
  margin-bottom: 28px;
  opacity: 0; animation: rise .6s .1s ease forwards;
}
.eyebrow-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--amber);
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }

/* Hero headline */
.hero-split {
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: 64px;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  padding: 80px 48px 100px;
}

/* Auth card */
.auth-card {
  background: #13141a;
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 20px;
  overflow: hidden;
  position: relative;
}
.auth-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(251,176,52,.5), transparent);
}
.auth-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.auth-tab {
  padding: 14px; text-align: center;
  font-size: 13px; font-weight: 500; color: #5e6175;
  cursor: pointer; background: transparent; border: none;
  font-family: 'DM Sans', sans-serif; transition: all .18s;
}
.auth-tab.active {
  color: #fbb034;
  background: rgba(251,176,52,.05);
  border-bottom: 2px solid #fbb034;
}
.auth-body { padding: 28px; }
.panel { display: none; }
.panel.active { display: block; }

/* Redirect to correct tab if there are errors */
.form-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;
}
.form-group { margin-bottom: 14px; }
.strength-bar { display: flex; gap: 3px; margin-top: 6px; }
.s-seg { flex: 1; height: 3px; border-radius: 2px; background: rgba(255,255,255,.06); transition: background .25s; }
.s-seg.w { background: #ef4444; }
.s-seg.m { background: #f97316; }
.s-seg.g { background: #eab308; }
.s-seg.s { background: #22c55e; }
.s-lbl { font-size: 10.5px; color: #5e6175; margin-top: 3px; }
.terms-note { font-size: 11px; color: #3e4055; text-align: center; margin-top: 12px; line-height: 1.6; }
.terms-note a { color: #5e6175; text-decoration: none; }
.terms-note a:hover { color: #fbb034; }

/* Form elements */
.form-label {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 12.5px; font-weight: 500; color: #f0f0f5; margin-bottom: 7px;
}
.form-label a { font-size: 11px; color: #5e6175; text-decoration: none; }
.form-label a:hover { color: #fbb034; }
.input-wrap { position: relative; }
.form-input {
  width: 100%; padding: 10px 12px; border-radius: 9px;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.09);
  color: #f0f0f5; font-family: 'DM Sans', sans-serif; font-size: 13px;
  transition: border-color .2s, background .2s;
}
.form-input::placeholder { color: #5e6175; }
.form-input:focus { outline: none; border-color: #fbb034; background: rgba(255,255,255,.08); }
.btn-primary {
  width: 100%; padding: 11px 16px; border-radius: 9px; margin-top: 6px;
  background: #fbb034; border: none; color: #0a0b0f; font-size: 13px; font-weight: 600;
  cursor: pointer; font-family: 'DM Sans', sans-serif;
  transition: all .18s;
}
.btn-primary:hover { background: #fcc050; transform: translateY(-1px); }
.divider-row {
  display: flex; align-items: center; gap: 12px; margin: 18px 0;
}
.divider-row::before, .divider-row::after {
  content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.06);
}
.divider-row span { font-size: 11px; color: #5e6175; font-weight: 500; }
.btn-google {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; padding: 11px 16px; border-radius: 9px;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.09);
  color: #f0f0f5; font-size: 13px; font-weight: 500; cursor: pointer;
  font-family: 'DM Sans', sans-serif; text-decoration: none;
  transition: all .18s;
}
.btn-google:hover { border-color: rgba(255,255,255,.15); background: rgba(255,255,255,.09); }

/* Hero left panel */
.hero-left { flex: 1; }

@media (max-width: 900px) {
  .hero-split { grid-template-columns: 1fr; }
  .auth-card { max-width: 420px; margin: 0 auto; }
}
.hero-h1 {
  font-family: 'Sora', sans-serif;
  font-size: clamp(44px, 7vw, 88px);
  font-weight: 800; line-height: 1.0;
  letter-spacing: -.02em;
  color: var(--text);
  margin-bottom: 28px;
  opacity: 0; animation: rise .7s .2s ease forwards;
}
.hero-h1 .line-amber {
  display: block;
  background: linear-gradient(90deg, var(--amber) 0%, #f97316 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-h1 .line-dim { color: rgba(240,240,245,.35); font-weight: 300; font-style: italic; }

.hero-sub {
  font-size: 17px; color: var(--muted); line-height: 1.7; max-width: 520px;
  margin-bottom: 44px;
  opacity: 0; animation: rise .7s .35s ease forwards;
}

.hero-actions {
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  opacity: 0; animation: rise .7s .5s ease forwards;
}
.btn-hero {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 28px; border-radius: 12px;
  font-size: 14px; font-weight: 600; text-decoration: none;
  background: var(--amber); color: #0a0b0f;
  box-shadow: 0 0 32px var(--amber-glow);
  transition: all .2s;
}
.btn-hero:hover { background: #fcc050; transform: translateY(-2px); box-shadow: 0 0 48px var(--amber-glow); }
.btn-hero-ghost {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 28px; border-radius: 12px;
  font-size: 14px; font-weight: 500; text-decoration: none;
  background: transparent; color: var(--muted);
  border: 1px solid var(--border);
  transition: all .2s;
}
.btn-hero-ghost:hover { border-color: var(--amber-mid); color: var(--text); }

/* Floating stats row */
.hero-stats {
  display: flex; align-items: center; gap: 40px; flex-wrap: wrap;
  margin-top: 72px; padding-top: 40px;
  border-top: 1px solid var(--border);
  opacity: 0; animation: rise .7s .7s ease forwards;
}
.stat-item { display: flex; flex-direction: column; gap: 4px; }
.stat-num { font-family: 'Sora', sans-serif; font-size: 28px; font-weight: 700; color: var(--amber); }
.stat-label { font-size: 12px; color: var(--muted); letter-spacing: .04em; }
.stat-divider { width: 1px; height: 36px; background: var(--border); }

/* ═══════════════════════════════════════
   MARQUEE STRIP
═══════════════════════════════════════ */
.marquee-strip {
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  background: var(--surface);
  padding: 14px 0; overflow: hidden;
  white-space: nowrap;
}
.marquee-track {
  display: inline-flex; gap: 48px;
  animation: marquee 22s linear infinite;
}
.marquee-track span {
  font-size: 11.5px; font-weight: 500; letter-spacing: .08em;
  text-transform: uppercase; color: var(--muted2);
}
.marquee-track span.hi { color: var(--amber); }
@keyframes marquee { from{transform:translateX(0)} to{transform:translateX(-50%)} }

/* ═══════════════════════════════════════
   HOW IT WORKS
═══════════════════════════════════════ */
.section { padding: 100px 48px; max-width: 1100px; margin: 0 auto; }
.section-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: .14em;
  text-transform: uppercase; color: var(--amber); margin-bottom: 16px;
}
.section-title {
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 4vw, 46px);
  font-weight: 700; line-height: 1.15;
  color: var(--text); margin-bottom: 16px;
}
.section-sub { font-size: 16px; color: var(--muted); max-width: 480px; line-height: 1.65; margin-bottom: 60px; }

/* Steps */
.steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
.step {
  background: var(--surface); padding: 36px 32px;
  position: relative; overflow: hidden;
  transition: background .2s;
}
.step:first-child { border-radius: var(--r) 0 0 var(--r); }
.step:last-child  { border-radius: 0 var(--r) var(--r) 0; }
.step:hover { background: var(--surface2); }
.step::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, var(--amber), transparent);
  opacity: 0; transition: opacity .2s;
}
.step:hover::before { opacity: 1; }
.step-num {
  font-family: 'Sora', sans-serif; font-size: 11px; font-weight: 700;
  letter-spacing: .1em; color: var(--amber); margin-bottom: 20px;
  display: flex; align-items: center; gap: 8px;
}
.step-num::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.step-icon {
  width: 44px; height: 44px; border-radius: 12px; margin-bottom: 18px;
  background: var(--amber-dim); border: 1px solid var(--amber-mid);
  display: flex; align-items: center; justify-content: center;
  color: var(--amber);
}
.step h3 { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 600; margin-bottom: 10px; }
.step p  { font-size: 13.5px; color: var(--muted); line-height: 1.65; }

/* ═══════════════════════════════════════
   FEATURES GRID
═══════════════════════════════════════ */
.features-wrap { background: var(--bg2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.features { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); }
.feature {
  background: var(--bg2); padding: 48px 40px;
  position: relative; overflow: hidden;
  transition: background .2s;
}
.feature:hover { background: var(--surface); }
.feature-icon {
  width: 48px; height: 48px; border-radius: 14px; margin-bottom: 22px;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border);
  background: var(--surface);
}
.feature h3 { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 600; margin-bottom: 10px; }
.feature p  { font-size: 14px; color: var(--muted); line-height: 1.7; }
.feature-badge {
  display: inline-flex; margin-top: 18px; padding: 4px 12px; border-radius: 999px;
  font-size: 10.5px; font-weight: 600; letter-spacing: .05em;
  background: var(--amber-dim); border: 1px solid var(--amber-mid); color: var(--amber);
}

/* ═══════════════════════════════════════
   ROLES SECTION
═══════════════════════════════════════ */
.roles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.role-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r); padding: 28px 26px;
  position: relative; overflow: hidden;
  transition: border-color .2s, transform .2s;
}
.role-card:hover { border-color: var(--amber-mid); transform: translateY(-4px); }
.role-card::after {
  content: '';
  position: absolute; bottom: -40px; right: -40px;
  width: 100px; height: 100px; border-radius: 50%;
  background: var(--amber-dim); pointer-events: none;
}
.role-icon {
  width: 42px; height: 42px; border-radius: 12px; margin-bottom: 18px;
  background: var(--amber-dim); border: 1px solid var(--amber-mid);
  display: flex; align-items: center; justify-content: center;
  color: var(--amber);
}
.role-tag {
  font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
  color: var(--amber); margin-bottom: 10px;
}
.role-card h3 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px; }
.role-card p  { font-size: 13px; color: var(--muted); line-height: 1.65; }
.role-list { margin-top: 16px; display: flex; flex-direction: column; gap: 7px; }
.role-list li {
  display: flex; align-items: center; gap: 8px;
  font-size: 12.5px; color: var(--muted); list-style: none;
}
.role-list li::before {
  content: ''; width: 5px; height: 5px; border-radius: 50%;
  background: var(--amber); flex-shrink: 0;
}

/* ═══════════════════════════════════════
   CTA BAND
═══════════════════════════════════════ */
.cta-band {
  margin: 0 48px 100px;
  border-radius: 24px;
  background: linear-gradient(135deg, var(--surface) 0%, rgba(251,176,52,.06) 100%);
  border: 1px solid var(--amber-mid);
  padding: 72px 64px;
  position: relative; overflow: hidden;
  text-align: center;
}
.cta-band::before {
  content: '';
  position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
  width: 500px; height: 300px; border-radius: 50%;
  background: radial-gradient(ellipse, rgba(251,176,52,.07), transparent 70%);
  pointer-events: none;
}
.cta-band h2 {
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 4vw, 48px); font-weight: 800;
  line-height: 1.1; margin-bottom: 18px;
  position: relative; z-index: 1;
}
.cta-band h2 span { color: var(--amber); }
.cta-band p { font-size: 16px; color: var(--muted); margin-bottom: 36px; position: relative; z-index: 1; }
.cta-band-actions { display: flex; align-items: center; justify-content: center; gap: 14px; position: relative; z-index: 1; }

/* ═══════════════════════════════════════
   FOOTER
═══════════════════════════════════════ */
footer {
  border-top: 1px solid var(--border);
  padding: 40px 48px;
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
}
.footer-logo { display: flex; align-items: center; gap: 8px; text-decoration: none; }
.footer-logo span { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--muted); letter-spacing: .06em; }
footer p { font-size: 12px; color: var(--muted2); }

/* ═══════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════ */
@keyframes rise { from{opacity:0;transform:translateY(22px)} to{opacity:1;transform:translateY(0)} }

/* Scroll reveal */
.reveal { opacity: 0; transform: translateY(24px); transition: opacity .65s ease, transform .65s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-delay-1 { transition-delay: .1s; }
.reveal-delay-2 { transition-delay: .2s; }
.reveal-delay-3 { transition-delay: .3s; }

/* ═══════════════════════════════════════
   MOBILE
═══════════════════════════════════════ */
@media (max-width: 768px) {
  nav { padding: 18px 20px; }
  .nav-links { display: none; }
  .hero { padding: 120px 20px 60px; }
  .section { padding: 70px 20px; }
  .steps { grid-template-columns: 1fr; gap: 2px; }
  .step:first-child { border-radius: var(--r) var(--r) 0 0; }
  .step:last-child  { border-radius: 0 0 var(--r) var(--r); }
  .features { grid-template-columns: 1fr; }
  .roles { grid-template-columns: 1fr; }
  .cta-band { margin: 0 16px 60px; padding: 48px 28px; }
  footer { padding: 32px 20px; flex-direction: column; align-items: flex-start; }
  .hero-stats { gap: 24px; }
  .stat-divider { display: none; }
  body { cursor: auto; }
  .cursor, .cursor-ring { display: none; }
}

</style>
</head>
<body>

<!-- Custom cursor -->
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- ══ NAV ══ -->
<nav id="navbar">
  <a href="#" class="nav-logo">
    <div class="nav-logo-icon">L</div>
    <span class="nav-logo-text">LIKHA</span>
  </a>
  <div class="nav-links">
    <a href="#how">How it works</a>
    <a href="#features">Features</a>
    <a href="#roles">For you</a>
  </div>
  <div class="nav-cta">
    <a href="/login" class="btn-ghost-sm">Sign in</a>
    <a href="/register" class="btn-amber">Get started</a>
  </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
  <div class="hero-grid">
    <svg viewBox="0 0 1440 900" fill="none" preserveAspectRatio="xMidYMid slice">
      <defs>
        <pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse">
          <path d="M 80 0 L 0 0 0 80" fill="none" stroke="white" stroke-width="1"/>
        </pattern>
      </defs>
      <rect width="1440" height="900" fill="url(#grid)"/>
    </svg>
  </div>

  <div class="hero-split">
   <div class="hero-left">
    <div class="eyebrow">
      <span class="eyebrow-dot"></span>
      Campus Innovation System
    </div>

    <h1 class="hero-h1">
      Every problem<br>
      <span class="line-amber">is an idea</span>
      <span class="line-dim"> waiting.</span>
    </h1>

    <p class="hero-sub">
      LIKHA collects real campus problems, clusters them by pattern,
      and generates AI-scored capstone project ideas — so students
      spend less time searching and more time building.
    </p>

    <div class="hero-actions">
      <a href="/register" class="btn-hero">
        <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
        Start exploring
      </a>
      <a href="#how" class="btn-hero-ghost">
        See how it works
        <i data-lucide="chevron-down" style="width:15px;height:15px;"></i>
      </a>
    </div>

    <div class="hero-stats">
      <div class="stat-item">
        <span class="stat-num">{{ $totalProblems ?? '0' }}</span>
        <span class="stat-label">Problems Reported</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">{{ $ideaCandidates ?? '0' }}</span>
        <span class="stat-label">Capstone Candidates</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">{{ $totalCategories ?? '0' }}</span>
        <span class="stat-label">Campus Categories</span>
      </div>
      <div class="stat-divider"></div>
      <div class="stat-item">
        <span class="stat-num">AI</span>
        <span class="stat-label">Powered Scoring</span>
      </div>
    </div>
    </div>

    {{-- RIGHT: inline auth card --}}
  <div class="auth-card">
    <div class="auth-tabs">
      <button class="auth-tab active" data-tab="login">Sign in</button>
      <button class="auth-tab" data-tab="register">Create account</button>
    </div>

    <div class="auth-body">

      {{-- LOGIN PANEL --}}
      <div class="panel active" id="panel-login">
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="form-group">
            <label class="form-label">Email address</label>
            <div class="input-wrap">
              <input type="email" name="email" class="form-input" placeholder="you@school.edu" required>
              {{-- mail icon --}}
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">
              Password
              <a href="#">Forgot password?</a>
            </label>
            <div class="input-wrap">
              <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn-primary">Sign in to LIKHA</button>
        </form>

        <div class="divider-row"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" width="16">
          Continue with Google
        </a>
      </div>

      {{-- REGISTER PANEL --}}
      <div class="panel" id="panel-register">
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <div class="form-row">
            <div>
              <label class="form-label">First name</label>
              <input type="text" name="first_name" class="form-input" placeholder="Juan" required>
            </div>
            <div>
              <label class="form-label">Last name</label>
              <input type="text" name="last_name" class="form-input" placeholder="dela Cruz" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-input" placeholder="you@school.edu" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="regPw" class="form-input"
              placeholder="Min. 8 characters" oninput="chkStr(this.value)" required>
            <div class="strength-bar">
              <div class="s-seg" id="ss1"></div>
              <div class="s-seg" id="ss2"></div>
              <div class="s-seg" id="ss3"></div>
              <div class="s-seg" id="ss4"></div>
            </div>
            <div class="s-lbl" id="sLbl">Enter a password</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-input"
              placeholder="Re-enter password" required>
          </div>
          <button type="submit" class="btn-primary">Create account</button>
        </form>

        <div class="divider-row"><span>OR</span></div>
        <a href="{{ route('google.login') }}" class="btn-google">
          <img src="https://developers.google.com/identity/images/g-logo.png" width="16">
          Continue with Google
        </a>
        <p class="terms-note">By registering you agree to our <a href="#">Terms</a> & <a href="#">Privacy Policy</a>.</p>
      </div>

    </div>
  </div>

  </div>
</section>

<!-- ══ MARQUEE ══ -->
<div class="marquee-strip">
  <div class="marquee-track">
    <span class="hi">▲ Submit Problems</span>
    <span>·</span>
    <span>AI Idea Generation</span>
    <span>·</span>
    <span class="hi">▲ Adviser Reviews</span>
    <span>·</span>
    <span>Capstone Ideas</span>
    <span>·</span>
    <span>Campus Analytics</span>
    <span>·</span>
    <span class="hi">▲ Severity Scoring</span>
    <span>·</span>
    <span>Confidence Levels</span>
    <span>·</span>
    <span>Category Insights</span>
    <span>·</span>
    <span class="hi">▲ Submit Problems</span>
    <span>·</span>
    <span>AI Idea Generation</span>
    <span>·</span>
    <span class="hi">▲ Adviser Reviews</span>
    <span>·</span>
    <span>Capstone Ideas</span>
    <span>·</span>
    <span>Campus Analytics</span>
    <span>·</span>
    <span class="hi">▲ Severity Scoring</span>
    <span>·</span>
    <span>Confidence Levels</span>
    <span>·</span>
    <span>Category Insights</span>
    <span>·</span>
  </div>
</div>

<!-- ══ HOW IT WORKS ══ -->
<div id="how">
  <div class="section">
    <div class="reveal">
      <p class="section-label">The Process</p>
      <h2 class="section-title">From complaint<br>to capstone — in three steps.</h2>
      <p class="section-sub">No more staring at a blank page wondering what to build. LIKHA turns what's broken around you into your next big project.</p>
    </div>

    <div class="steps reveal reveal-delay-1">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-icon"><i data-lucide="file-plus" style="width:20px;height:20px;"></i></div>
        <h3>Report a Problem</h3>
        <p>Students and staff submit real issues they face on campus — from enrollment bottlenecks to broken facilities. Takes under 2 minutes.</p>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-icon"><i data-lucide="cpu" style="width:20px;height:20px;"></i></div>
        <h3>AI Finds the Pattern</h3>
        <p>LIKHA clusters similar reports, scores severity and confidence, then generates a ranked list of actionable capstone project ideas.</p>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-icon"><i data-lucide="graduation-cap" style="width:20px;height:20px;"></i></div>
        <h3>Build What Matters</h3>
        <p>Students browse validated ideas, advisers review and score them, and the best ideas get adopted into real capstone projects.</p>
      </div>
    </div>
  </div>
</div>

<!-- ══ FEATURES ══ -->
<div id="features" class="features-wrap">
  <div style="max-width:1100px; margin:0 auto; padding:100px 48px 0;">
    <div class="reveal">
      <p class="section-label">What's Inside</p>
      <h2 class="section-title">Built for the<br>whole campus ecosystem.</h2>
    </div>
  </div>
  <div style="max-width:1100px; margin:0 auto; padding:48px;">
    <div class="features reveal reveal-delay-1">

      <div class="feature">
        <div class="feature-icon"><i data-lucide="brain" style="width:22px;height:22px;color:#fbb034;"></i></div>
        <h3>AI Idea Generation</h3>
        <p>Problems are grouped by keyword clusters and scored across four dimensions — frequency, impact, severity, and confidence — to surface the most relevant capstone ideas automatically.</p>
        <span class="feature-badge">Powered by custom scoring</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="bar-chart-2" style="width:22px;height:22px;color:#3b82f6;"></i></div>
        <h3>Category Analytics</h3>
        <p>Visual breakdowns of which campus areas are generating the most problems, who is being affected, and how frequently issues occur — giving advisers and admins real data to act on.</p>
        <span class="feature-badge" style="background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2);color:#3b82f6;">Live dashboard</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="shield-check" style="width:22px;height:22px;color:#10b981;"></i></div>
        <h3>Adviser Evaluation</h3>
        <p>Advisers review AI-generated ideas and submit their own feasibility, impact, complexity, and innovation scores. The final idea score blends both — 70% system, 30% adviser — for a balanced recommendation.</p>
        <span class="feature-badge" style="background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.2);color:#10b981;">Hybrid scoring</span>
      </div>

      <div class="feature">
        <div class="feature-icon"><i data-lucide="git-branch" style="width:22px;height:22px;color:#f97316;"></i></div>
        <h3>Duplicate Detection</h3>
        <p>Live keyword matching flags similar problems as you type, preventing redundant submissions and helping students upvote existing issues instead — making the community data cleaner and stronger.</p>
        <span class="feature-badge" style="background:rgba(249,115,22,.1);border-color:rgba(249,115,22,.2);color:#f97316;">Real-time</span>
      </div>

    </div>
  </div>
</div>

<!-- ══ ROLES ══ -->
<div id="roles">
  <div class="section">
    <div class="reveal">
      <p class="section-label">Who it's for</p>
      <h2 class="section-title">Everyone has a role<br>in solving campus problems.</h2>
      <p class="section-sub">LIKHA is built around three types of users, each with a clear purpose in the system.</p>
    </div>

    <div class="roles">

      <div class="role-card reveal">
        <div class="role-icon"><i data-lucide="user" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Student</p>
        <h3>Report & Discover</h3>
        <p>You see the problems every day. LIKHA gives you a way to turn those frustrations into meaningful research.</p>
        <ul class="role-list">
          <li>Submit campus problems</li>
          <li>Upvote existing issues</li>
          <li>Browse AI-generated ideas</li>
          <li>Save ideas for your capstone</li>
          <li>Track idea status</li>
        </ul>
      </div>

      <div class="role-card reveal reveal-delay-1">
        <div class="role-icon"><i data-lucide="clipboard-check" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Adviser</p>
        <h3>Guide & Validate</h3>
        <p>Review AI-generated ideas and add your academic expertise to ensure the best projects get built.</p>
        <ul class="role-list">
          <li>Review idea candidates</li>
          <li>Score feasibility & impact</li>
          <li>Leave feedback comments</li>
          <li>Recommend or revise ideas</li>
          <li>Track your review progress</li>
        </ul>
      </div>

      <div class="role-card reveal reveal-delay-2">
        <div class="role-icon"><i data-lucide="shield" style="width:19px;height:19px;"></i></div>
        <p class="role-tag">Admin</p>
        <h3>Moderate & Analyze</h3>
        <p>Keep the system healthy and use campus-wide data to identify the most critical areas for improvement.</p>
        <ul class="role-list">
          <li>Approve or reject submissions</li>
          <li>View category analytics</li>
          <li>Monitor trending problems</li>
          <li>Manage campus categories</li>
          <li>Oversee all users</li>
        </ul>
      </div>

    </div>
  </div>
</div>

<!-- ══ CTA BAND ══ -->
<div class="cta-band reveal">
  <h2>Your campus has problems.<br><span>LIKHA has ideas.</span></h2>
  <p>Join the community turning everyday frustrations into solutions that actually get built.</p>
  <div class="cta-band-actions">
    <a href="/register" class="btn-hero">
      <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
      Create your account
    </a>
    <a href="/feedback" class="btn-hero-ghost">
      Browse problems
    </a>
  </div>
</div>

<!-- ══ FOOTER ══ -->
<footer>
  <a href="#" class="footer-logo">
    <div class="nav-logo-icon" style="width:26px;height:26px;font-size:11px;border-radius:7px;">L</div>
    <span>LIKHA</span>
  </a>
  <p>A campus problem-to-capstone intelligence system.</p>
  <p style="font-size:11px;color:var(--muted2);">Built with Laravel · Alpine.js · Chart.js</p>
</footer>

<script>
// ── Custom cursor ──
const cursor     = document.getElementById('cursor');
const cursorRing = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;

document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.left = mx + 'px';
  cursor.style.top  = my + 'px';
});

// Ring follows with lag
(function animRing() {
  rx += (mx - rx) * 0.12;
  ry += (my - ry) * 0.12;
  cursorRing.style.left = rx + 'px';
  cursorRing.style.top  = ry + 'px';
  requestAnimationFrame(animRing);
})();

// Expand on hover
document.querySelectorAll('a, button').forEach(el => {
  el.addEventListener('mouseenter', () => {
    cursor.style.width = '20px'; cursor.style.height = '20px';
    cursorRing.style.width = '54px'; cursorRing.style.height = '54px';
  });
  el.addEventListener('mouseleave', () => {
    cursor.style.width = '10px'; cursor.style.height = '10px';
    cursorRing.style.width = '36px'; cursorRing.style.height = '36px';
  });
});

// ── Navbar scroll ──
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

// ── Scroll reveal ──
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Lucide icons ──
lucide.createIcons();

// Tab switching
document.querySelectorAll('.auth-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
  });
});

// If Laravel redirected back with errors, open the right panel
@if ($errors->has('email') && !$errors->has('name'))
  document.querySelector('[data-tab="login"]').click();
@elseif ($errors->has('name'))
  document.querySelector('[data-tab="register"]').click();
@endif

// Password strength meter
function chkStr(v) {
  const segs = ['ss1','ss2','ss3','ss4'];
  const cls  = ['w','m','g','s'];
  const lbs  = ['Too short','Weak','Getting there','Strong'];
  segs.forEach(id => document.getElementById(id).className = 's-seg');
  if (!v.length) { document.getElementById('sLbl').textContent = 'Enter a password'; return; }
  let score = 0;
  if (v.length >= 8)       score++;
  if (/[A-Z]/.test(v))     score++;
  if (/[0-9]/.test(v))     score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  for (let i = 0; i < score; i++) document.getElementById(segs[i]).classList.add(cls[i]);
  document.getElementById('sLbl').textContent = lbs[score - 1] || 'Too short';
}
</script>
</body>
</html>