@extends('layouts.app')

@section('title', 'Problem Summary')
@section('subtitle', 'Browse common issues reported by the campus community')

@section('content')

<style>
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.22);
  }

  @keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .anim-1 { animation: fadeInUp .45s ease both; }
  .anim-2 { animation: fadeInUp .45s .08s ease both; }

  /* ── Category card ── */
  .cat-card {
    position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px; border-radius: 16px;
    border: 1px solid #f3f4f6;
    background: white;
    text-decoration: none;
    transition: border-color .2s, transform .2s, box-shadow .2s;
  }
  .dark .cat-card { background: #1e293b; border-color: rgba(255,255,255,.06); }
  .cat-card:hover {
    border-color: var(--amber-border);
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,.10);
  }
  .dark .cat-card:hover { box-shadow: 0 12px 28px rgba(0,0,0,.35); }

  /* Hover shimmer */
  .cat-card::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(110deg, rgba(251,176,52,.06) 0%, transparent 60%);
    opacity: 0; transition: opacity .3s;
    pointer-events: none;
  }
  .cat-card:hover::before { opacity: 1; }

  /* ── Icon box ── */
  .cat-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: var(--amber-dim); border: 1px solid var(--amber-border);
    transition: transform .2s;
  }
  .cat-card:hover .cat-icon { transform: scale(1.1) rotate(-4deg); }

  /* ── Count pill ── */
  .cat-count {
    flex-shrink: 0; padding: 6px 14px; border-radius: 10px;
    font-size: .8125rem; font-weight: 700; font-family: 'Sora', sans-serif;
    background: linear-gradient(to right, #fbb034, #f97316);
    color: #0e0f14;
    box-shadow: 0 3px 10px rgba(251,176,52,.3);
    transition: box-shadow .2s, transform .2s;
  }
  .cat-card:hover .cat-count {
    box-shadow: 0 5px 16px rgba(251,176,52,.45);
    transform: translateY(-1px);
  }

  /* ── Arrow ── */
  .cat-arrow {
    font-size: .85rem; color: #d1d5db;
    transition: color .2s, transform .2s;
    margin-left: 10px;
  }
  .cat-card:hover .cat-arrow { color: var(--amber); transform: translate(3px,-3px); }
</style>

<div class="max-w-4xl mx-auto space-y-6">

  {{-- ── Section intro ── --}}
  <div class="anim-1 flex items-center justify-between flex-wrap gap-3">
    <div>
      <h2 class="text-xl font-bold text-gray-800 dark:text-white" style="font-family:'Sora',sans-serif;">
        Problem Summary
      </h2>
      <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">
        Browse common issues reported by the campus community
      </p>
    </div>
    @if(!$categories->isEmpty())
    <span class="text-xs font-semibold px-3 py-1.5 rounded-full border"
          style="background:var(--amber-dim); border-color:var(--amber-border); color:var(--amber);">
      {{ $categories->count() }} {{ Str::plural('category', $categories->count()) }}
    </span>
    @endif
  </div>

  @php
  $icons = [
    'Enrollment'       => 'file-text',
    'Academic Process' => 'book-open',
    'Facilities'       => 'building',
    'Library'          => 'library',
    'Scheduling'       => 'calendar',
  ];
  @endphp

  {{-- ── Empty state ── --}}
  @if($categories->isEmpty())
  <div class="anim-2 rounded-2xl border border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800 p-14 text-center">
    <p class="text-3xl mb-3">📭</p>
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No categories reported yet.</p>
    <a href="{{ route('feedback.create') }}"
       class="inline-block mt-4 px-5 py-2 rounded-xl text-sm font-semibold text-black transition"
       style="background:var(--amber); box-shadow:0 4px 14px rgba(251,176,52,.3);">
      Submit the first problem
    </a>
  </div>

  {{-- ── Category grid ── --}}
  @else
  <div class="anim-2 grid sm:grid-cols-2 gap-4">
    @foreach($categories as $category)
    <a href="{{ route('feedback.category', $category->category) }}" class="cat-card group">

      <div class="flex items-center gap-3 relative z-10 min-w-0">

        {{-- Icon --}}
        <div class="cat-icon">
          <i data-lucide="{{ $icons[$category->category] ?? 'folder' }}"
             class="w-[18px] h-[18px]" style="color:var(--amber);"></i>
        </div>

        {{-- Label --}}
        <div class="min-w-0">
          <p class="font-semibold text-sm text-gray-800 dark:text-white truncate
             group-hover:text-amber-500 transition" style="font-family:'Sora',sans-serif;">
            {{ $category->category }}
          </p>
          <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ $category->total }} {{ Str::plural('report', $category->total) }}
          </p>
        </div>

      </div>

      {{-- Right side --}}
      <div class="flex items-center gap-2 relative z-10 shrink-0">
        <span class="cat-count">{{ $category->total }}</span>
        <span class="cat-arrow">↗</span>
      </div>

    </a>
    @endforeach
  </div>
  @endif

</div>

@endsection