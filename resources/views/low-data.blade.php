@extends('layouts.app')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
@endpush

@section('content')

<style>
  :root {
    --amber:      #fbb034;
    --adim:       rgba(251,176,52,0.10);
    --amid:       rgba(251,176,52,0.22);
    --aborder-h:  rgba(251,176,52,0.30);
  }

  :root:not(.dark) {
    --amber:      #b57318;
    --adim:       rgba(186,117,23,0.08);
    --amid:       rgba(186,117,23,0.18);
    --aborder-h:  rgba(186,117,23,0.35);
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .anim-in { animation: fadeInUp .5s ease both; }

  /* ── Empty state container ── */
  .empty-container {
    display: flex; align-items: center; justify-content: center;
    min-height: 600px; padding: 40px 20px;
  }

  /* ── Empty state card ── */
  .empty-card {
    position: relative; overflow: hidden;
    background: #13141a; border: 1px solid rgba(255,255,255,.07);
    border-radius: 24px; padding: 60px 40px;
    max-width: 500px; width: 100%;
    text-align: center;
    transition: border-color .2s, transform .2s;
  }
  :root:not(.dark) .empty-card {
    background: #ffffff; border-color: rgba(0,0,0,.08);
    box-shadow: 0 4px 12px rgba(0,0,0,.06);
  }

  /* Decorative glow */
  .empty-card::before {
    content: ''; position: absolute;
    top: -100px; right: -100px;
    width: 200px; height: 200px;
    border-radius: 50%; background: var(--adim);
    pointer-events: none;
  }

  /* ── Icon ── */
  .empty-icon {
    width: 80px; height: 80px;
    border-radius: 20px; margin: 0 auto 24px;
    background: var(--adim); border: 2px solid var(--amid);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; position: relative; z-index: 10;
    transition: transform .3s;
  }

  /* ── Title ── */
  .empty-title {
    font-family: 'Sora', sans-serif;
    font-size: 24px; font-weight: 800;
    color: inherit; margin: 20px 0 12px;
    position: relative; z-index: 10;
  }

  /* ── Description ── */
  .empty-desc {
    font-size: 14px; line-height: 1.7;
    color: #7e8194; margin-bottom: 36px;
    position: relative; z-index: 10;
  }
  :root:not(.dark) .empty-desc { color: #6b6880; }

  /* ── Action buttons ── */
  .empty-actions {
    display: flex; flex-direction: column; gap: 12px;
    position: relative; z-index: 10;
  }

  .btn-primary {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; padding: 12px 24px; border-radius: 12px;
    background: linear-gradient(135deg, #fbb034, #f97316);
    color: #0e0f14; font-size: 13.5px; font-weight: 700;
    font-family: 'DM Sans', sans-serif; text-decoration: none;
    border: none; cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 14px rgba(251,176,52,.3);
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(251,176,52,.4);
  }

  .btn-secondary {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; padding: 11px 24px; border-radius: 12px;
    background: transparent; color: var(--amber);
    font-size: 13.5px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; text-decoration: none;
    border: 1.5px solid var(--amid); cursor: pointer;
    transition: all .15s;
  }
  .btn-secondary:hover {
    border-color: var(--aborder-h);
    background: var(--adim);
    transform: translateY(-1px);
  }

  /* ── Hint text ── */
  .empty-hint {
    font-size: 12px; color: #5e6175;
    margin-top: 24px; padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,.05);
    position: relative; z-index: 10;
  }
  :root:not(.dark) .empty-hint {
    color: #9a97b0;
    border-color: rgba(0,0,0,.06);
  }
</style>

<div class="empty-container">
  <div class="empty-card anim-in">
    <div class="empty-icon">📊</div>
    <h2 class="empty-title">Not Enough Data Yet</h2>
    <p class="empty-desc">
      This category doesn't have enough reports to generate a capstone idea. Help us grow by submitting more problems!
    </p>
    <div class="empty-actions">
      <a href="{{ route('feedback.create') }}" class="btn-primary">
        <i data-lucide="plus" style="width:16px;height:16px;"></i>
        Submit a Problem
      </a>
      <a href="{{ route('feedback.summary') }}" class="btn-secondary">
        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
        Back to Summary
      </a>
    </div>
    <div class="empty-hint">
      Need 3+ reports in a category to unlock AI-powered ideas
    </div>
  </div>
</div>

@endsection