<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIKHA - @yield('title')</title>


@vite(['resources/css/app.css','resources/js/app.js'])

<style>
    /* ── LIKHA Login Layout ── */
.lk-wrap {
  min-height: 100vh;
  background: #0a0b0f;
  display: flex;
  position: relative;
  overflow: hidden;
  font-family: 'DM Sans', sans-serif;
}
.lk-left {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px;
  position: relative;
  z-index: 2;
}
.lk-right {
  width: 460px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 52px;
  position: relative;
  z-index: 2;
}
.orb1 {
  position: absolute; top: -100px; left: -100px;
  width: 480px; height: 480px; border-radius: 50%;
  background: radial-gradient(circle, rgba(251,176,52,.09) 0%, transparent 70%);
  pointer-events: none;
}
.orb2 {
  position: absolute; bottom: -60px; right: 360px;
  width: 400px; height: 400px; border-radius: 50%;
  background: radial-gradient(circle, rgba(249,115,22,.06) 0%, transparent 70%);
  pointer-events: none;
}
.grid-bg {
  position: absolute; inset: 0; pointer-events: none; opacity: .03;
}
.grain {
  position: absolute; inset: 0; pointer-events: none; opacity: .022;
  background-image: url("data:image/svg+xml,..."); /* same as welcome page */
  background-size: 128px;
}
.divider-v {
  position: absolute; top: 0; bottom: 0; right: 460px; width: 1px;
  background: linear-gradient(to bottom, transparent, rgba(255,255,255,.07) 30%, rgba(255,255,255,.07) 70%, transparent);
}

/* Logo */
.logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.logo-icon {
  width: 32px; height: 32px; border-radius: 9px;
  background: linear-gradient(135deg, #fbb034, #f07d10);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Sora', sans-serif; font-weight: 800; font-size: 14px; color: #0a0b0f;
  box-shadow: 0 0 18px rgba(251,176,52,.35);
}
.logo-text { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 18px; letter-spacing: .06em; color: #f0f0f5; }

/* Left panel */
.left-body { flex: 1; display: flex; flex-direction: column; justify-content: center; padding-bottom: 24px; }
.eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 999px;
  background: rgba(251,176,52,.1); border: 1px solid rgba(251,176,52,.22);
  font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase;
  color: #fbb034; margin-bottom: 24px; width: fit-content;
}
.edot { width: 6px; height: 6px; border-radius: 50%; background: #fbb034; animation: pdot 2s ease-in-out infinite; }
@keyframes pdot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.7)} }
.left-h {
  font-family: 'Sora', sans-serif; font-size: 36px; font-weight: 800;
  line-height: 1.1; letter-spacing: -.02em; color: #f0f0f5; margin-bottom: 16px;
}
.left-h .amb {
  background: linear-gradient(90deg, #fbb034, #f97316);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.left-sub { font-size: 14px; color: #7e8194; line-height: 1.7; max-width: 360px; margin-bottom: 40px; }
.feature-list { display: flex; flex-direction: column; gap: 14px; }
.feat { display: flex; align-items: flex-start; gap: 12px; }
.feat-icon {
  width: 32px; height: 32px; border-radius: 9px;
  background: rgba(251,176,52,.1); border: 1px solid rgba(251,176,52,.18);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.feat-text { display: flex; flex-direction: column; gap: 2px; }
.feat-text strong { font-size: 13px; font-weight: 500; color: #f0f0f5; }
.feat-text span { font-size: 12px; color: #7e8194; }
.left-foot { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #3e4055; }
.left-foot a { color: #5e6175; text-decoration: none; }
.left-foot a:hover { color: #7e8194; }

/* Card */
.right-inner { width: 100%; max-width: 380px; }
.card {
  background: #13141a; border: 1px solid rgba(255,255,255,.08);
  border-radius: 18px; padding: 36px 32px; position: relative; overflow: hidden;
}
.card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(251,176,52,.4), transparent);
}
.card-top { text-align: center; margin-bottom: 28px; }
.card-tag {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px; border-radius: 999px;
  background: rgba(251,176,52,.08); border: 1px solid rgba(251,176,52,.15);
  font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase;
  color: #fbb034; margin-bottom: 16px;
}
.card-h { font-family: 'Sora', sans-serif; font-size: 19px; font-weight: 700; color: #f0f0f5; margin-bottom: 6px; }
.card-sub { font-size: 12.5px; color: #7e8194; }
.form-group { margin-bottom: 18px; }
.form-label { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px; color: #9a9bb0; }
.form-label a { font-size: 11.5px; color: #5e6175; text-decoration: none; transition: color .15s; }
.form-label a:hover { color: #fbb034; }
.input-wrap { position: relative; }
.form-input {
  width: 100%; background: #1a1b23; border: 1px solid rgba(255,255,255,.08);
  border-radius: 10px; padding: 10px 40px 10px 14px;
  font-size: 13.5px; color: #f0f0f5; font-family: 'DM Sans', sans-serif;
  outline: none; transition: border-color .15s, box-shadow .15s;
}
.form-input::placeholder { color: #3e4055; }
.form-input:focus { border-color: rgba(251,176,52,.45); box-shadow: 0 0 0 3px rgba(251,176,52,.08); }
.input-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #3e4055; }
.btn-primary {
  width: 100%; background: #fbb034; color: #0a0b0f;
  border: none; border-radius: 10px; padding: 11px;
  font-size: 13.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
  cursor: pointer; box-shadow: 0 0 24px rgba(251,176,52,.3); transition: all .18s; margin-top: 4px;
}
.btn-primary:hover { background: #fcc050; box-shadow: 0 0 36px rgba(251,176,52,.4); transform: translateY(-1px); }
.divider-row { display: flex; align-items: center; gap: 12px; margin: 20px 0; }
.divider-row::before, .divider-row::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.06); }
.divider-row span { font-size: 11px; color: #3e4055; letter-spacing: .06em; }
.btn-google {
  width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
  background: #1a1b23; border: 1px solid rgba(255,255,255,.08); border-radius: 10px;
  padding: 10px; cursor: pointer; transition: all .18s;
  font-size: 13px; color: #9a9bb0; font-family: 'DM Sans', sans-serif; text-decoration: none;
}
.btn-google:hover { background: #20222b; border-color: rgba(255,255,255,.15); color: #f0f0f5; }
.g-logo { width: 18px; height: 18px; }
.card-foot { text-align: center; margin-top: 22px; font-size: 12.5px; color: #5e6175; }
.card-foot a { color: #fbb034; text-decoration: none; font-weight: 500; }
.card-foot a:hover { text-decoration: underline; }
.alert-error {
  background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.25);
  border-radius: 8px; padding: 10px 14px; font-size: 12.5px; color: #f87171; margin-bottom: 18px;
}

/* Form Row for side-by-side inputs */
.form-row { display: flex; gap: 16px; }
.form-row .form-group { flex: 1; }

/* Password Strength Indicator */
.strength-bar {
  display: flex; gap: 4px; margin-top: 8px; margin-bottom: 8px;
}
.strength-seg {
  height: 4px; border-radius: 2px; flex: 1;
  background: #374151; transition: background-color .3s;
}
.strength-seg.s1 { background: #ef4444; }
.strength-seg.s2 { background: #f97316; }
.strength-seg.s3 { background: #fbbf24; }
.strength-seg.s4 { background: #10b981; }
.strength-label { font-size: 11.5px; color: #9a9bb0; }

/* Steps List */
.steps-list { display: flex; flex-direction: column; gap: 16px; }
.step-row {
  display: flex; gap: 12px; align-items: flex-start;
}
.step-num {
  width: 28px; height: 28px; min-width: 28px;
  border-radius: 8px; background: rgba(251,176,52,.1);
  border: 1px solid rgba(251,176,52,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 600; color: #fbb034;
}
.step-text {
  display: flex; flex-direction: column; gap: 4px; padding-top: 2px;
}
.step-text strong {
  font-size: 13px; font-weight: 500; color: #f0f0f5;
}
.step-text span {
  font-size: 12px; color: #7e8194;
}

/* Mobile */
@media (max-width: 768px) {
  .lk-left { display: none; }
  .lk-right { width: 100%; padding: 32px 20px; }
  .divider-v { display: none; }
  .form-row { flex-direction: column; gap: 0; }
}
   
body{
        background: #0a0b0f;
        color: #f0f0f5;
    }
</style>


</head>

<body class="font-sans">


<main class="min-h-screen flex items-center justify-center px-4">
    @yield('content')
</main>

@stack('scripts')

</body>
</html>
