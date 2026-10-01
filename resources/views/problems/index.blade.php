@extends('layouts.app')

{{-- No page-title bar: the sidebar already marks Discover active. The subtitle is
     kept so the page still explains what it lists. --}}
@section('subtitle', 'Browse institutional problems by momentum, support, severity, and fresh capstone activity.')

@section('content')
@include('layouts.partials.design-system')

<style>
    /* ── Discover — view-scoped refinements (LIKHA tokens only) ── */

    .lk-search { position: relative; }
    .lk-search .lk-input { padding-left: 36px; }
    .lk-search-icon {
        position: absolute; left: 12px; top: 50%;
        transform: translateY(-50%);
        width: 15px; height: 15px;
        color: var(--text3);
        pointer-events: none;
    }

    .lk-problem-title {
        font-family: 'Sora', sans-serif;
        font-size: 16px; font-weight: 700; line-height: 1.4;
        color: var(--text);
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lk-problem-desc {
        margin-top: 8px; font-size: 13px; line-height: 1.65;
        color: var(--text2);
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .lk-signal {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11.5px; font-weight: 600; color: var(--text3);
    }
    .lk-signal i { width: 12px; height: 12px; }

    .lk-why {
        margin-top: 14px; padding: 10px 12px;
        border-radius: 10px;
        background: var(--surface2);
        border: 1px solid var(--border);
        border-left: 2px solid var(--amber-mid);
    }
    .lk-why-label {
        display: block; margin-bottom: 3px;
        font-size: 10px; font-weight: 700;
        letter-spacing: .08em; text-transform: uppercase;
        color: var(--text3);
    }
    .lk-why p { font-size: 12.5px; line-height: 1.6; color: var(--text2); }

    .lk-vote {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 999px;
        font-size: 12.5px; font-weight: 600; font-family: 'DM Sans', sans-serif;
        background: var(--surface2); border: 1px solid var(--border);
        color: var(--text2); cursor: pointer;
        transition: all .18s;
    }
    .lk-vote i { width: 15px; height: 15px; }
    .lk-vote:hover { border-color: var(--amber-mid); background: var(--amber-dim); color: var(--amber); }
    .lk-vote.is-on { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }

    /* Vendor Tailwind pagination -> LIKHA amber (view-level override) */
    .lk-pagination nav a,
    .lk-pagination nav span {
        background: var(--surface);
        border-color: var(--border);
        color: var(--text2);
        border-radius: 9px;
        margin: 0;
        font-weight: 600;
        transition: all .15s;
    }
    html.dark .lk-pagination nav a,
    html.dark .lk-pagination nav span {
        background: var(--surface);
        border-color: var(--border);
        color: var(--text2);
    }
    .lk-pagination nav a:hover,
    html.dark .lk-pagination nav a:hover {
        background: var(--amber-dim);
        border-color: var(--amber-mid);
        color: var(--amber);
    }
    .lk-pagination nav a:focus,
    html.dark .lk-pagination nav a:focus {
        outline: none;
        background: var(--amber-dim);
        border-color: var(--amber-mid);
        color: var(--amber);
        box-shadow: 0 0 0 3px var(--amber-dim);
    }
    .lk-pagination nav [aria-current='page'] span {
        background: var(--amber-dim);
        border-color: var(--amber-mid);
        color: var(--amber);
    }
    .lk-pagination nav [aria-disabled='true'] { opacity: .55; }
    .lk-pagination nav .shadow-sm { box-shadow: none; }
    .lk-pagination nav p,
    html.dark .lk-pagination nav p { color: var(--text3); }

    /* ── Mobile: keep every Discover control inside the viewport ──
       The scope and sort pills live in flex rows whose automatic minimum width
       is their widest pill, and the card headers are non-wrapping flex rows.
       Together they push the card past the screen instead of reflowing, which
       makes the content area scroll sideways. The rules below let those rows
       wrap and let the pill groups take the card's full width.

       Every pill is atomic (nowrap, 0 1 auto) so a long option such as
       "Highest Severity" moves onto its own line rather than being squeezed or
       clipped. No overflow is used to achieve this — the row simply wraps. */
    @media (max-width: 640px) {
        .lk-card, .lk-search, .lk-search input, .lk-input,
        select.lk-input, .lk-pagination nav { min-width: 0; max-width: 100%; }

        /* Card headers: the descriptive sub-copy drops to its own line. */
        .co-section-head { flex-wrap: wrap; row-gap: 4px; }
        .co-section-head > * { min-width: 0; }

        .discover-filters { gap: 10px; }
        .discover-filters > .lk-badge { align-self: flex-start; }

        /* Scope and Sort each own a full-width row; their pills wrap inside it. */
        .discover-filters > .sort-pills {
            flex: 1 1 100%; width: 100%;
            flex-wrap: wrap; min-width: 0; gap: 8px;
        }

        /* Modest mobile reduction — still comfortably above body-copy size. */
        .sort-pill {
            font-size: 11px; padding: 7px 10px; gap: 5px;
            white-space: nowrap; flex: 0 1 auto; min-width: 0;
        }
        .sort-pill i { width: 14px; height: 14px; }

        /* Long idea titles in the recent-opportunities rows must truncate
           rather than stretch the row. */
        .ri-text { min-width: 0; }
    }
</style>

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ══ FIND ══ --}}
    <section class="lk-card anim-1 p-5 sm:p-6">
        <div class="co-section-head">
            <span class="co-section-dot"></span>
            <span class="co-section-title">Find a problem</span>
            <span class="co-section-sub">Search by keyword, or narrow to a single category</span>
        </div>

        <form method="GET" action="{{ route('discover') }}" class="grid gap-3">
            <div>
                <label for="discover-search" class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Search</label>
                <div class="lk-search">
                    <i data-lucide="search" class="lk-search-icon"></i>
                    <input
                        id="discover-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Queue delays, registrar, Wi-Fi"
                        class="lk-input"
                    />
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2 md:items-end">
                <div>
                    <label for="discover-category" class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Category</label>
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
                        <i data-lucide="filter" class="h-4 w-4" aria-hidden="true"></i>
                        Apply
                    </button>
                    @if (request()->hasAny(['search', 'category', 'sort']))
                        <a href="{{ route('discover') }}" class="btn-ghost text-sm">Reset</a>
                    @endif
                </div>
            </div>

            @if (request()->hasAny(['search', 'category']))
                <div class="filter-tags mt-3 border-t pt-3" style="border-color: var(--border);">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Filtering by</span>
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
    </section>

    {{-- ══ SORT ══ --}}
    <section class="lk-card anim-2 p-3">
        <div class="discover-filters flex flex-wrap items-center gap-3">
            <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Problems</span>
            <div class="sort-pills flex-1" role="navigation" aria-label="Problem scope">
                @foreach ([
                    'all' => 'All',
                    'office' => 'Office Problems',
                    'community' => 'Community Problems',
                ] as $scope => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['scope' => $scope === 'all' ? null : $scope, 'page' => null]) }}"
                        @if($activeScope === $scope) aria-current="page" @endif
                        @class(['sort-pill', 'active' => $activeScope === $scope])
                    >{{ $label }}</a>
                @endforeach
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Sort</span>
            <div class="sort-pills flex-1" role="navigation" aria-label="Sort problems">
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
                        @class(['sort-pill', 'active' => $activeSort === $value])
                    >
                        <i data-lucide="{{ $meta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $meta['label'] }}</span>
                    </a>
                @endforeach
            </div>
            <span class="lk-badge badge-muted shrink-0">
                {{ $feedbacks->total() }} {{ Str::plural('problem', $feedbacks->total()) }}
            </span>
        </div>
    </section>

    {{-- ══ SCAN PROBLEMS ══ --}}
    <section class="grid gap-4 lg:grid-cols-2">
        @forelse ($feedbacks as $feedback)
            <article class="lk-card anim-{{ min($loop->iteration, 5) }} flex flex-col p-5">
                {{-- A. CONTEXT --}}
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->user?->name ?? 'Anonymous contributor' }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $feedback->created_at->diffForHumans() }} &bull; {{ $feedback->cluster_name }}</p>
                    </div>
                    <span class="lk-badge badge-amber shrink-0">#{{ Str::slug($feedback->category, '') }}</span>
                </div>

                @if($feedback->is_capstone_worthy)
                    <div class="mt-3">
                        <span class="lk-badge badge-green">
                            <i data-lucide="sparkles" aria-hidden="true"></i>
                            Capstone Opportunity
                        </span>
                    </div>
                @endif

                @if($feedback->office)
                    <div class="mt-3">
                        <span class="lk-badge badge-blue">Office Problem</span>
                        <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">Associated with {{ $feedback->office->name }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Representative: {{ $feedback->office->representative?->name ?? 'Unavailable' }}</p>
                @endif

                {{-- B. PROBLEM --}}
                <h2 class="lk-problem-title mt-3">{{ $feedback->title }}</h2>
                <p class="lk-problem-desc">{{ $feedback->description }}</p>

                {{-- D. DSS INTERPRETATION (primary metadata) --}}
                @php
                    $sevBadge = $feedback->severity_level === 'High' ? 'badge-red' : ($feedback->severity_level === 'Medium' ? 'badge-amber' : 'badge-green');
                    $conBadge = $feedback->confidence_level === 'High' ? 'badge-green' : ($feedback->confidence_level === 'Medium' ? 'badge-blue' : 'badge-muted');
                @endphp
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="lk-badge {{ $sevBadge }}">Severity {{ number_format($feedback->severity_score, 1) }}</span>
                    <span class="lk-badge {{ $conBadge }}">Confidence {{ number_format($feedback->confidence_score, 1) }}</span>
                </div>

                {{-- C. EVIDENCE SIGNALS (compact inline metadata) --}}
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="lk-signal">
                        <i data-lucide="thumbs-up" aria-hidden="true"></i>
                        {{ $feedback->votes_count }} support
                    </span>
                    <span class="lk-signal">
                        <i data-lucide="file-text" aria-hidden="true"></i>
                        {{ $feedback->comments_count }} evidence
                    </span>
                    <span class="lk-signal">
                        <i data-lucide="repeat" aria-hidden="true"></i>
                        {{ $feedback->recurring_report_count }} recurring
                    </span>
                </div>

                {{-- E. WHY IT MATTERS (supporting callout) --}}
                <div class="lk-why">
                    <span class="lk-why-label">Why it matters</span>
                    <p>{{ $feedback->why_it_matters }}</p>
                </div>

                {{-- F. ACTIONS --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-3" style="border-color: var(--border);">
                    <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
                        @csrf
                        <button
                            type="submit"
                            @class(['lk-vote', 'is-on' => $feedback->has_supported])
                            aria-label="{{ $feedback->has_supported ? 'Remove your support' : 'Support this problem' }}"
                            aria-pressed="{{ $feedback->has_supported ? 'true' : 'false' }}"
                            title="{{ $feedback->has_supported ? 'Supported' : 'Support this problem' }}"
                        >
                            <i data-lucide="thumbs-up" aria-hidden="true"></i>
                            <span>{{ $feedback->votes_count }}</span>
                        </button>
                    </form>
                    <a href="{{ route('feedback.show', $feedback) }}" class="btn-amber text-sm">
                        View details
                        <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
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
        <div class="lk-pagination pt-2">{{ $feedbacks->withQueryString()->links() }}</div>
    @endif

    {{-- ══ SECONDARY: RECENT CAPSTONE OPPORTUNITIES ══ --}}
    <section class="lk-card anim-2 overflow-hidden">
        <div class="dc-head">
            <div class="min-w-0">
                <div class="dc-title">Recent Capstone Opportunities</div>
                <div class="dc-sub">Freshly generated from the DSS — a starting point for your capstone.</div>
            </div>
            <span class="lk-badge badge-muted shrink-0">{{ $recentIdeas->count() }} new</span>
        </div>
        <div class="dc-body">
            @forelse ($recentIdeas as $idea)
                <a
                    href="{{ route('feedback.category', ['category' => $idea->category, 'idea' => $idea->idea_title]) }}"
                    class="row-item"
                >
                    <span class="lk-badge badge-amber shrink-0">{{ $idea->category }}</span>
                    <span class="ri-text flex-1">{{ $idea->idea_title }}</span>
                    <span class="ri-vote" title="Overall evaluation" aria-label="Overall evaluation {{ number_format($idea->overall_score, 2) }}">{{ number_format($idea->overall_score, 2) }}</span>
                </a>
            @empty
                <p class="empty-text">No generated ideas yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
