@extends('layouts.app')

@section('title', 'Home Feed')
@section('subtitle', 'What is happening on campus right now, the signals behind it, and the capstone opportunities the DSS has recently produced.')

@section('content')
@include('layouts.partials.design-system')

<style>
    /* ── Home — view-scoped refinements (LIKHA tokens only) ──
       .lk-problem-title / .lk-problem-desc / .lk-signal / .lk-why / .lk-vote
       are defined in Discover's view scope, so they are mirrored here to keep
       the shared problem-card anatomy identical on both surfaces. */

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

    /* Home-scoped: compact platform metric strip (unboxed, My Contribution pattern) */
    .lk-metric-label {
        display: flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 600;
        letter-spacing: .06em; text-transform: uppercase;
        color: var(--text3);
    }
    .lk-metric-label i { width: 14px; height: 14px; }
    .lk-metric-value {
        font-family: 'Sora', sans-serif;
        font-size: 24px; font-weight: 700; line-height: 1.2;
        color: var(--text);
    }
    .lk-metric-note { font-size: 12px; color: var(--text3); }
    .lk-metric-cell { min-width: 0; }
    @media (min-width: 640px) {
        .lk-metric-cell + .lk-metric-cell {
            padding-left: 20px;
            border-left: 1px solid var(--border);
        }
    }

    /* Home-scoped: generated-idea row */
    .lk-idea-row {
        display: block; padding: 12px 14px;
        border: 1px solid var(--border); border-radius: 12px;
        background: var(--surface);
        transition: all .18s;
    }
    .lk-idea-row:hover { border-color: var(--amber-mid); background: var(--amber-dim); }
    .lk-idea-title {
        font-family: 'Sora', sans-serif; font-size: 13.5px; font-weight: 700; line-height: 1.4;
        color: var(--text);
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .lk-idea-meta { display: flex; align-items: center; gap: 8px; margin-top: 6px; font-size: 11.5px; color: var(--text3); }
    .lk-idea-score {
        font-family: 'Sora', sans-serif; font-weight: 700; color: var(--amber);
        background: var(--amber-dim); border: 1px solid var(--amber-mid);
        border-radius: 999px; padding: 1px 8px; font-size: 11.5px;
    }

    /* Home-scoped: compact category chip */
    .lk-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 11px; border-radius: 999px;
        font-size: 12px; font-weight: 600;
        background: var(--amber-dim); color: var(--amber);
        border: 1px solid var(--amber-mid);
        transition: all .18s;
    }
    .lk-chip:hover { background: var(--amber-mid); }
    .lk-chip span { opacity: .7; font-weight: 600; }

    /* Home-scoped: hero copy size (arbitrary values are absent from the served CSS) */
    .lk-home-copy { font-size: 13.5px; }

    /* Home-scoped: latest-problems + aside shell, single column until 1280px */
    .lk-home-shell { display: grid; }
    @media (min-width: 1280px) {
        .lk-home-shell { grid-template-columns: minmax(0,1.5fr) minmax(0,0.9fr); }
    }
</style>

<div class="mx-auto max-w-7xl space-y-6">
    {{-- ── 1. Orientation header ── compact, single card, token-based ── --}}
    <section class="lk-card anim-1 p-6 sm:p-7">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <span class="co-hero-eyebrow">What's happening on campus</span>
                <h1 class="mt-3 max-w-2xl font-['Sora'] text-2xl font-bold leading-snug sm:text-3xl">Campus friction, made visible.</h1>
                <p class="mt-3 max-w-2xl lk-home-copy leading-6" style="color: var(--text2);">
                    Home surfaces the institutional problems students are reporting right now, the support and evidence
                    signals behind each one, and the capstone opportunities LIKHA's decision support system has begun to
                    generate from them.
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2.5">
                <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a Problem</a>
                <a href="{{ route('discover') }}" class="btn-ghost text-sm">Explore Discover</a>
            </div>
        </div>
    </section>

    {{-- ── 2. Compact metric strip ── unboxed, one container, text-2xl figures ── --}}
    <section class="lk-card anim-2 p-5 sm:p-6">
        <div class="grid gap-5 sm:grid-cols-3">
            <div class="lk-metric-cell">
                <span class="lk-metric-label"><i data-lucide="shield-check" aria-hidden="true"></i> Approved Problems</span>
                <p class="lk-metric-value mt-1.5">{{ $totalProblems }}</p>
                <p class="lk-metric-note mt-1">Validated signals from the campus community.</p>
            </div>
            <div class="lk-metric-cell">
                <span class="lk-metric-label"><i data-lucide="lightbulb" aria-hidden="true"></i> Idea-Ready Categories</span>
                <p class="lk-metric-value mt-1.5">{{ $ideaCandidates }}</p>
                <p class="lk-metric-note mt-1">Already approaching recommendation thresholds.</p>
            </div>
            <div class="lk-metric-cell">
                <span class="lk-metric-label"><i data-lucide="hash" aria-hidden="true"></i> Tracked Hashtags</span>
                <p class="lk-metric-value mt-1.5">{{ $totalCategories }}</p>
                <p class="lk-metric-note mt-1">Institutional domains visible in the feed.</p>
            </div>
        </div>
    </section>

    {{-- ── 3. Latest problems ── concise 6-up preview using Discover's card anatomy ── --}}
    @php
        $preview = $feed->take(6);
        $previewCount = $preview->count();
    @endphp

    <section class="lk-home-shell grid gap-6">
        <div class="min-w-0">
            <div class="co-section-head">
                <span class="co-section-dot" aria-hidden="true"></span>
                <h2 class="co-section-title">Latest Problems</h2>
                <span class="lk-badge badge-muted shrink-0">{{ $previewCount }} of {{ $totalProblems }}</span>
            </div>

            @if ($previewCount > 0)
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($preview as $feedback)
                        <article class="lk-card anim-{{ min($loop->iteration, 5) }} flex flex-col p-5">
                            {{-- A. CONTEXT --}}
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->is_anonymous ? 'Anonymous contributor' : ($feedback->user?->name ?? 'Campus contributor') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $feedback->created_at->diffForHumans() }} &bull; {{ $feedback->cluster_name }}</p>
                                </div>
                                <span class="lk-badge badge-amber shrink-0">#{{ Str::slug($feedback->category, '') }}</span>
                            </div>

                            @if ($feedback->is_capstone_worthy)
                                <div class="mt-3">
                                    <span class="lk-badge badge-green">
                                        <i data-lucide="sparkles" aria-hidden="true"></i>
                                        Capstone Opportunity
                                    </span>
                                </div>
                            @endif

                            {{-- B. PROBLEM --}}
                            <h3 class="lk-problem-title mt-3">{{ $feedback->title }}</h3>
                            <p class="lk-problem-desc">{{ $feedback->description }}</p>

                            {{-- C. DSS INTERPRETATION (primary metadata) --}}
                            @php
                                $sevBadge = $feedback->severity_level === 'High' ? 'badge-red' : ($feedback->severity_level === 'Medium' ? 'badge-amber' : 'badge-green');
                                $conBadge = $feedback->confidence_level === 'High' ? 'badge-green' : ($feedback->confidence_level === 'Medium' ? 'badge-blue' : 'badge-muted');
                            @endphp
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="lk-badge {{ $sevBadge }}">Severity {{ number_format($feedback->severity_score, 1) }}</span>
                                <span class="lk-badge {{ $conBadge }}">Confidence {{ number_format($feedback->confidence_score, 1) }}</span>
                            </div>

                            {{-- D. EVIDENCE SIGNALS (compact inline metadata) --}}
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <span class="lk-signal">
                                    <i data-lucide="thumbs-up" aria-hidden="true"></i>
                                    Support {{ $feedback->votes_count }}
                                </span>
                                <span class="lk-signal">
                                    <i data-lucide="file-text" aria-hidden="true"></i>
                                    Evidence {{ $feedback->comments_count }}
                                </span>
                                <span class="lk-signal">
                                    <i data-lucide="repeat" aria-hidden="true"></i>
                                    Recurring {{ $feedback->recurring_report_count }}
                                </span>
                            </div>

                            {{-- E. WHY IT MATTERS --}}
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
                    @endforeach
                </div>

                {{-- 4. Preview indicator — Home previews, Discover owns the full list --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs" style="color: var(--text3);">
                        Showing {{ $previewCount }} of {{ $totalProblems }} {{ Str::plural('problem', $totalProblems) }}.
                    </p>
                    <a href="{{ route('discover') }}" class="btn-ghost text-sm">
                        View all in Discover
                        <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </div>
            @else
                <div class="empty-state lk-card">
                    <div class="empty-ico"><i data-lucide="inbox" class="h-5 w-5" aria-hidden="true"></i></div>
                    <p class="empty-text">No approved problems on the board yet.</p>
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a problem</a>
                        <a href="{{ route('discover') }}" class="btn-ghost text-sm">Explore Discover</a>
                    </div>
                </div>
            @endif
        </div>

        <aside class="min-w-0 space-y-6">
            {{-- 5. Recently generated ideas — Home-unique DSS preview (display-only) --}}
            <div class="lk-card anim-3 p-5">
                <div class="co-section-head">
                    <span class="co-section-dot" aria-hidden="true"></span>
                    <h3 class="co-section-title">Recently Generated Ideas</h3>
                    <a href="{{ route('capstone.opportunities') }}" class="ml-auto shrink-0 text-xs font-semibold" style="color: var(--amber);">View opportunities</a>
                </div>
                <div>
                    @forelse ($generatedIdeas as $idea)
                        <a href="{{ route('feedback.category', ['category' => $idea->category, 'idea' => $idea->idea_title]) }}" class="lk-idea-row mb-1.5 last:mb-0">
                            <p class="lk-idea-title">{{ $idea->idea_title }}</p>
                            <div class="lk-idea-meta">
                                <span class="lk-badge badge-amber">{{ $idea->category }}</span>
                                <span class="lk-idea-score" title="Overall evaluation" aria-label="Overall evaluation {{ number_format($idea->overall_score, 2) }}">Score {{ number_format($idea->overall_score, 2) }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-[13px] leading-6" style="color: var(--text3);">Generated capstone recommendations will appear here once thresholds are met.</p>
                    @endforelse
                </div>
            </div>

            {{-- 6. Trending — compact top-3 preview, Discover owns the full ranking --}}
            <div class="lk-card anim-4 p-5">
                <div class="co-section-head">
                    <span class="co-section-dot" aria-hidden="true"></span>
                    <h3 class="co-section-title">Trending Problems</h3>
                    <a href="{{ route('discover', ['sort' => 'trending']) }}" class="ml-auto shrink-0 text-xs font-semibold" style="color: var(--amber);">View all</a>
                </div>
                @forelse ($trending->take(3) as $problem)
                    <a href="{{ route('feedback.show', $problem) }}" class="row-item">
                        <span class="ri-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="ri-text">{{ $problem->title }}</span>
                        <span class="ri-vote">{{ $problem->votes_count }}</span>
                    </a>
                @empty
                    <p class="text-[13px]" style="color: var(--text3);">Nothing trending yet.</p>
                @endforelse
            </div>

            {{-- 7. Compact category preview — Discover owns filtering --}}
            <div class="lk-card anim-5 p-5">
                <div class="co-section-head">
                    <span class="co-section-dot" aria-hidden="true"></span>
                    <h3 class="co-section-title">Active Hashtags</h3>
                    <a href="{{ route('discover') }}" class="ml-auto shrink-0 text-xs font-semibold" style="color: var(--amber);">Filter in Discover</a>
                </div>
                <div class="flex flex-wrap gap-2">
                    @forelse ($categories->take(4) as $category)
                        <a href="{{ route('discover', ['category' => $category['name']]) }}" class="lk-chip">
                            #{{ Str::slug($category['name'], '') }}
                            <span>{{ $category['total'] }}</span>
                        </a>
                    @empty
                        <p class="text-[13px]" style="color: var(--text3);">No categories tracked yet.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection