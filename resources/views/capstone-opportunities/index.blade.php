@extends('layouts.app')

{{-- No page-title bar: the sidebar already marks Capstone Opportunities active.
     The subtitle is kept so the page still explains what it lists. --}}
@section('subtitle', 'Institutional problems already identified as potential capstone projects.')

@section('content')
@include('layouts.partials.design-system')

<style>
    /* ── Capstone Opportunities — view-scoped refinements (LIKHA tokens only) ──
       The shared problem-card anatomy is mirrored from Discover's view scope so
       the same problem reads identically here, on Discover and on Home. */

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
</style>

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ══ SCOPE ══ — slim, uncarded filter row, matching the category page. ══ --}}
    <div class="anim-1" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
        <span style="font-size:10px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--text3)">Scope</span>
        <div class="sort-pills" role="navigation" aria-label="Opportunity scope">
            @foreach ([
                'all' => 'All',
                'office' => 'Office-Backed',
                'community' => 'Community',
            ] as $scope => $label)
                <a
                    href="{{ request()->fullUrlWithQuery(['scope' => $scope === 'all' ? null : $scope, 'page' => null]) }}"
                    @if($activeScope === $scope) aria-current="page" @endif
                    @class(['sort-pill', 'active' => $activeScope === $scope])
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ══ IDENTIFIED OPPORTUNITIES ══ --}}
    <section class="grid gap-4 lg:grid-cols-2">
        @forelse ($opportunities as $opportunity)
            @php
                $identifiedAt = $opportunity->capstone_marked_at
                    ? \Illuminate\Support\Carbon::parse($opportunity->capstone_marked_at)
                    : null;
            @endphp

            <article class="lk-card anim-{{ min($loop->iteration, 5) }} flex flex-col p-5">

                {{-- A. IDENTITY --}}
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        @if ($opportunity->department)
                            <p class="text-sm font-semibold" style="color: var(--text);">{{ $opportunity->department }}</p>
                        @endif
                        <p class="lk-signal">
                            <i data-lucide="calendar" class="h-3 w-3" aria-hidden="true"></i>
                            Identified {{ $identifiedAt ? $identifiedAt->diffForHumans() : 'recently' }}
                            by {{ $opportunity->capstoneMarkedBy?->name ?? 'an institutional office' }}
                        </p>
                        @if ($opportunity->office)
                            <p class="mt-2 text-xs font-semibold" style="color: var(--blue);">Office-Backed Opportunity · {{ $opportunity->office->name }}</p>
                            <p class="text-xs" style="color: var(--text3);">Representative: {{ $opportunity->office->representative?->name ?? 'Unavailable' }}</p>
                            <p class="text-xs" style="color: var(--text3);">Official contact: {{ $opportunity->office->contact_email ?? 'Not provided' }}</p>
                        @else
                            <p class="mt-2 text-xs font-semibold" style="color: var(--text3);">Community Opportunity</p>
                        @endif
                    </div>
                    <span class="lk-badge badge-amber shrink-0">{{ $opportunity->category }}</span>
                </div>

                {{-- B. PROBLEM --}}
                <h2 class="lk-problem-title mt-3">{{ $opportunity->title }}</h2>
                <p class="lk-problem-desc">{{ $opportunity->description }}</p>

                {{-- C. EVIDENCE SIGNALS (compact inline metadata) --}}
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="lk-signal">
                        <i data-lucide="thumbs-up" aria-hidden="true"></i>
                        {{ $opportunity->votes_count }} support
                    </span>
                    <span class="lk-signal">
                        <i data-lucide="file-text" aria-hidden="true"></i>
                        {{ $opportunity->comments_count }} evidence
                    </span>
                    @if ($opportunity->priority_office)
                        <span class="lk-signal">
                            <i data-lucide="flag" aria-hidden="true"></i>
                            Priority office: {{ $opportunity->priority_office }}
                        </span>
                    @endif
                </div>

                {{-- D. ACTIONS --}}
                <div class="mt-4 flex flex-wrap items-center gap-3 border-t pt-3" style="border-color: var(--border);">
                    <a href="{{ route('feedback.show', $opportunity) }}" class="btn-ghost text-sm">
                        View Problem
                        <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('feedback.category', array_filter(['category' => $opportunity->category, 'scope' => $activeScope === 'all' ? null : $activeScope])) }}" class="btn-amber text-sm">
                        <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                        Explore DSS Ideas
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full empty-state lk-card anim-1">
                <div class="empty-ico"><i data-lucide="lightbulb" class="h-5 w-5" aria-hidden="true"></i></div>
                <p class="empty-text">
                    @if ($activeScope === 'office')
                        No office-backed opportunities yet.
                    @elseif ($activeScope === 'community')
                        No community opportunities yet.
                    @else
                        No capstone opportunities identified yet.
                    @endif
                </p>
                <p class="lk-problem-desc">
                    @if ($activeScope === 'office')
                        No institutional office has marked an approved problem as a capstone opportunity yet.
                        Switch to All to see community opportunities.
                    @elseif ($activeScope === 'community')
                        No community-reported problem has been identified as a capstone opportunity yet.
                        Switch to All to see office-backed opportunities.
                    @else
                        Institutional offices mark approved problems as capstone opportunities once the evidence is strong enough.
                    @endif
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    @if ($activeScope !== 'all')
                        <a href="{{ route('capstone.opportunities') }}" class="btn-ghost text-sm">Show all opportunities</a>
                    @endif
                    <a href="{{ route('discover') }}" class="btn-ghost text-sm">Browse Discover</a>
                    <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a Problem</a>
                </div>
            </div>
        @endforelse
    </section>

    @if ($opportunities->hasPages())
        <div class="lk-pagination pt-2">{{ $opportunities->withQueryString()->links() }}</div>
    @endif

    {{-- ══ COMMUNITY-GENERATED IDEAS ══
         DSS concepts raised by community reports alone, with no office behind
         them. This is a separate thing from the qualified opportunities above,
         which are approved problems an office marked capstone-worthy. The
         Office-Backed scope is about office work, so it never lists these. --}}
    @if ($activeScope !== 'office' && $communityGeneratedIdeas->isNotEmpty())
        <section class="lk-card anim-1 p-5">
            <div class="co-section-head">
                <span class="co-section-dot" aria-hidden="true"></span>
                <h2 class="co-section-title">Community-Generated Ideas</h2>
            </div>
            <p class="co-section-sub">
                Project concepts the DSS generated from community-reported problems, with no office attached.
                Open one to see the evidence and reasoning behind it.
            </p>

            <div class="mt-3 grid gap-4 md:grid-cols-2">
                @foreach ($communityGeneratedIdeas as $communityIdea)
                    <article class="rounded-xl border p-4" style="border-color: var(--border); background: var(--surface2);">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h3 class="lk-problem-title text-sm" style="margin: 0;">
                                {{ $communityIdea->ai_title ?: $communityIdea->idea_title }}
                            </h3>
                            <span class="lk-badge badge-amber shrink-0">{{ $communityIdea->category }}</span>
                        </div>

                        @if ($communityIdea->ai_description)
                            <p class="lk-problem-desc mt-2">{{ $communityIdea->ai_description }}</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            @if ($communityIdea->overall_score !== null)
                                <span class="lk-signal">
                                    <i data-lucide="gauge" aria-hidden="true"></i>
                                    Overall {{ number_format((float) $communityIdea->overall_score, 2) }}
                                </span>
                            @endif
                            @if ($communityIdea->recommendation)
                                <span class="lk-signal">
                                    <i data-lucide="sparkles" aria-hidden="true"></i>
                                    {{ $communityIdea->recommendation }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-3 border-t pt-3" style="border-color: var(--border);">
                            <a href="{{ route('feedback.category', ['category' => $communityIdea->category, 'scope' => 'community']) }}" class="btn-ghost text-sm">
                                View Idea
                                <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
