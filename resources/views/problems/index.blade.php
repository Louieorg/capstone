@extends('layouts.app')

@section('title', 'Discover')
@section('subtitle', 'Browse institutional problems by momentum, support, severity, and fresh capstone activity.')

@section('content')
@include('layouts.partials.design-system')

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ══ FILTERS + RECENT IDEAS ══ --}}
    <section class="grid gap-4 xl:grid-cols-[1.5fr,0.5fr]">

        <form method="GET" action="{{ route('discover') }}" class="lk-card p-5">
            <div class="grid gap-3 md:grid-cols-[1.3fr,0.9fr,auto] md:items-end">

                <div>
                    <label for="discover-search" class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            id="discover-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Queue delays, registrar, Wi-Fi"
                            class="lk-input pl-9"
                        />
                    </div>
                </div>

                <div>
                    <label for="discover-category" class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Category</label>
                    <select
                        id="discover-category"
                        name="category"
                        class="lk-input"
                    >
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 md:justify-end">
                    <button type="submit" class="btn-amber">
                        <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                        Apply
                    </button>
                    @if (request()->hasAny(['search', 'category', 'sort']))
                        <a href="{{ route('discover') }}" class="btn-ghost text-sm">Reset</a>
                    @endif
                </div>
            </div>

            @if (request()->hasAny(['search', 'category']))
                <div class="mt-3 filter-tags border-t border-slate-200/50 dark:border-white/10 pt-3">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">Filtering by</span>
                    @if (request('search'))
                        <span class="filter-tag">
                            "{{ request('search') }}"
                            <button type="button" class="filter-tag-remove" onclick="this.closest('form').querySelector('[name=search]').value=''; this.closest('form').submit();" aria-label="Remove search filter"><i data-lucide="x" class="h-3 w-3"></i></button>
                        </span>
                    @endif
                    @if (request('category'))
                        <span class="filter-tag">
                            #{{ Str::slug(request('category'), '') }}
                            <button type="button" class="filter-tag-remove" onclick="this.closest('form').querySelector('[name=category]').value=''; this.closest('form').submit();" aria-label="Remove category filter"><i data-lucide="x" class="h-3 w-3"></i></button>
                        </span>
                    @endif
                </div>
            @endif
        </form>

        <div class="lk-card p-5">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">
                <i data-lucide="sparkles" class="h-3 w-3" style="color:var(--amber)"></i>
                Recent Capstone Opportunities
            </p>
            <div class="mt-2 space-y-2">
                @forelse ($recentIdeas as $idea)
                    <a href="{{ route('feedback.category', ['category' => $idea->category, 'idea' => $idea->idea_title]) }}" class="block rounded-xl border border-slate-200/50 dark:border-white/10 p-3 transition hover:border-amber-300 hover:bg-amber-50/60 dark:hover:bg-white/5">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">{{ $idea->category }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $idea->idea_title }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Overall score {{ number_format($idea->overall_score, 2) }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No generated ideas yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ══ SORT PILLS ══ --}}
    <section class="sort-pills" role="navigation" aria-label="Sort problems">
        @php
            $sorts = [
                'trending'  => ['label' => 'Trending', 'icon' => 'trending-up'],
                'newest'    => ['label' => 'Newest', 'icon' => 'clock'],
                'supported' => ['label' => 'Most Supported', 'icon' => 'thumbs-up'],
                'severity'  => ['label' => 'Highest Severity', 'icon' => 'alert-triangle'],
            ];
        @endphp
        @foreach ($sorts as $value => $meta)
            <a
                href="{{ request()->fullUrlWithQuery(['sort' => $value, 'page' => null]) }}"
                @if($activeSort === $value) aria-current="page" @endif
                class="sort-pill {{ $activeSort === $value ? 'active' : '' }}"
            >
                <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4"></i>
                {{ $meta['label'] }}
            </a>
        @endforeach
    </section>

    {{-- ══ RESULTS ══ --}}
    <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-2">
        @forelse ($feedbacks as $feedback)
            <article class="lk-card p-5 flex flex-col">
                @if($feedback->is_capstone_worthy)
                    <div class="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full badge-green">
                        <i data-lucide="sparkles" class="h-3 w-3"></i>
                        Capstone Opportunity
                    </div>
                @endif

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $feedback->title }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $feedback->created_at->diffForHumans() }} · {{ $feedback->user?->name ?? 'Anonymous contributor' }}</p>
                    </div>
                    <span class="shrink-0 badge-amber">#{{ Str::slug($feedback->category, '') }}</span>
                </div>

                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $feedback->description }}</p>

                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="lk-badge badge-muted">{{ $feedback->votes_count }} support</span>
                    <span class="lk-badge badge-muted">{{ $feedback->comments_count }} evidence</span>
                    <span class="lk-badge badge-muted">{{ $feedback->recurring_report_count }} recurring</span>
                    @php
                        $sevBadge = $feedback->severity_level === 'High' ? 'badge-red' : ($feedback->severity_level === 'Medium' ? 'badge-amber' : 'badge-green');
                        $conBadge = $feedback->confidence_level === 'High' ? 'badge-green' : ($feedback->confidence_level === 'Medium' ? 'badge-blue' : 'badge-muted');
                    @endphp
                    <span class="lk-badge {{ $sevBadge }}">Severity {{ number_format($feedback->severity_score, 1) }}</span>
                    <span class="lk-badge {{ $conBadge }}">Confidence {{ number_format($feedback->confidence_score, 1) }}</span>
                </div>

                <p class="mt-4 border-l-2 border-amber-200/50 dark:border-amber-500/30 pl-3 text-sm italic leading-6 text-slate-500 dark:text-slate-400">
                    {{ $feedback->why_it_matters }}
                </p>

                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200/50 dark:border-white/10 pt-3">
                    <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
                        @csrf
                        <button type="submit" aria-label="{{ $feedback->has_supported ? 'Remove your support' : 'Support this problem' }}" aria-pressed="{{ $feedback->has_supported ? 'true' : 'false' }}" title="{{ $feedback->has_supported ? 'Supported' : 'Support this problem' }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $feedback->has_supported ? 'border-amber-500 bg-amber-100 text-amber-800 dark:border-amber-400 dark:bg-amber-500/20 dark:text-amber-200' : 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-500/10' }}">
                            <i data-lucide="thumbs-up" class="h-4 w-4" aria-hidden="true"></i>
                            <span>{{ $feedback->votes_count }}</span>
                        </button>
                    </form>
                    <a href="{{ route('feedback.show', $feedback) }}" class="btn-amber text-sm">
                        View details
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full empty-state lk-card">
                <div class="empty-ico"><i data-lucide="inbox" class="h-5 w-5"></i></div>
                <p class="empty-text">No problems matched the current filters.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ route('discover') }}" class="btn-ghost text-sm">Clear filters</a>
                    <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a problem</a>
                </div>
            </div>
        @endforelse
    </section>

    @if ($feedbacks->hasPages())
        <div class="pt-2">{{ $feedbacks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection