@extends('layouts.app')

@section('title', 'My Saved Ideas')
@section('subtitle', 'The capstone opportunities you saved from the DSS, with the problem behind each one still one click away.')

@section('content')
@include('layouts.partials.design-system')

<style>
    /* ── My Saved Ideas — view-scoped refinements (LIKHA tokens only) ──
       Colors, font and type sizes come from the shared tokens so this page
       tracks the DSS surfaces instead of relying on one-off literals. */

    .lk-si-text { color: var(--text); }
    .lk-si-text2 { color: var(--text2); }
    .lk-si-text3 { color: var(--text3); }
    .lk-si-amber { color: var(--amber); }
    .lk-si-border { border-color: var(--border); }

    .lk-si-sora { font-family: 'Sora', sans-serif; }
    .lk-si-h { font-size: 15px; }
    .lk-si-copy { font-size: 13.5px; }
    .lk-si-meta { font-size: 11.5px; }

    .lk-si-clamp {
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

@php
    $statuses = ['Exploring', 'Adopted', 'In Progress', 'Completed'];

    $statusBadges = [
        'Exploring' => 'badge-muted',
        'Adopted' => 'badge-blue',
        'In Progress' => 'badge-amber',
        'Completed' => 'badge-green',
    ];
@endphp

@if ($ideas->isNotEmpty())
    @section('page-action')
        <a href="{{ route('discover') }}" class="btn-ghost text-sm">
            <i data-lucide="compass" class="h-4 w-4" aria-hidden="true"></i>
            Discover more problems
        </a>
    @endsection
@endif

<div class="mx-auto max-w-5xl space-y-4">

    @forelse ($ideas as $idea)
        @php
            $evaluation = $idea->ideaEvaluation;
            $canonicalTitle = $evaluation?->idea_title ?: $idea->title;
            $aiTitle = ($evaluation?->ai_title && $evaluation->ai_title !== $canonicalTitle) ? $evaluation->ai_title : null;
            $overallScore = $evaluation?->overall_score;
            $finalScore = $evaluation?->final_score;
            $adjustedScore = ($finalScore !== null && (float) $finalScore !== (float) $overallScore) ? $finalScore : null;
            $rec = $evaluation?->recommendation;
            $recClass = $rec === 'Highly Recommended' ? 'badge-green' : ($rec === 'Recommended' ? 'badge-blue' : 'badge-red');
            $responsibleOffice = isset($responsibleOffices) ? $responsibleOffices->get($idea->category) : null;
            $opportunityUrl = route('feedback.category', ['category' => $idea->category, 'idea' => $idea->title]).'#idea-'.Str::slug($idea->title);
            $statusBadge = $statusBadges[$idea->status] ?? 'badge-muted';
        @endphp

        <article class="lk-card anim-{{ min($loop->iteration, 5) }} p-5">

            {{-- CATEGORY + STATUS BADGE, STATUS CONTROL --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="lk-badge badge-amber">{{ $idea->category }}</span>
                    <span class="lk-badge {{ $statusBadge }}">{{ $idea->status }}</span>
                </div>

                <form method="POST" action="{{ route('idea.updateStatus', $idea->id) }}" class="shrink-0">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="status-{{ $idea->id }}">
                        Status for {{ $canonicalTitle }}
                    </label>
                    <select id="status-{{ $idea->id }}" name="status" onchange="this.form.submit()"
                            class="lk-input w-auto cursor-pointer py-1.5 text-xs">
                        @foreach ($statuses as $option)
                            <option value="{{ $option }}" @selected($idea->status === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <noscript>
                        <button type="submit" class="btn-ghost mt-2" style="padding:6px 12px;font-size:11.5px">
                            Update
                        </button>
                    </noscript>
                </form>
            </div>

            {{-- CANONICAL DSS TITLE --}}
            <div class="mt-3">
                <h3 class="lk-si-sora lk-si-h font-bold leading-snug lk-si-text">{{ $canonicalTitle }}</h3>
            </div>

            {{-- AI WORDING ENHANCEMENT (optional, secondary to the DSS result) --}}
            @if ($aiTitle)
                <section class="co-ai" aria-label="AI-enhanced wording (optional)">
                    <div class="co-ai-head">
                        <span class="lk-badge badge-amber">
                            <i data-lucide="sparkles" class="h-3 w-3" aria-hidden="true"></i>
                            AI-ENHANCED
                        </span>
                    </div>
                    <p class="co-ai-note">Optional wording enhancement &mdash; the DSS recommendation and scores above remain unchanged.</p>
                    <p class="lk-si-sora lk-si-copy font-bold lk-si-text">{{ $aiTitle }}</p>
                </section>
            @endif

            {{-- DESCRIPTION --}}
            <p class="mt-3 lk-si-clamp lk-si-copy leading-6 lk-si-text2">
                {{ $idea->description }}
            </p>

            {{-- DSS METADATA + RESPONSIBLE OFFICE --}}
            <div class="mt-3 space-y-1 lk-si-meta lk-si-text3">
                @if ($evaluation)
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="bar-chart-2" class="h-3 w-3" aria-hidden="true"></i>
                            Overall evaluation:
                            <span class="lk-si-sora lk-si-copy font-bold lk-si-amber">{{ is_numeric($overallScore) ? number_format((float) $overallScore, 2) : '—' }}</span>
                        </span>

                        @if ($evaluation->recommendation)
                            <span class="lk-badge {{ $recClass }}">{{ $evaluation->recommendation }}</span>
                        @endif

                        @if ($adjustedScore !== null)
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="sliders-horizontal" class="h-3 w-3" aria-hidden="true"></i>
                                Adviser-adjusted:
                                <span class="font-semibold lk-si-text">{{ is_numeric($adjustedScore) ? number_format((float) $adjustedScore, 2) : $adjustedScore }}</span>
                            </span>
                        @endif
                    </p>
                @endif

                @if ($responsibleOffice)
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="building-2" class="h-3 w-3" aria-hidden="true"></i>
                            Responsible office:
                            <span class="font-semibold lk-si-text">{{ $responsibleOffice }}</span>
                        </span>
                    </p>
                @endif

                <p class="flex items-center gap-1.5">
                    <i data-lucide="calendar" class="h-3 w-3" aria-hidden="true"></i>
                    Saved {{ $idea->created_at->diffForHumans() }}
                    <span aria-hidden="true">&middot;</span>
                    <time datetime="{{ $idea->created_at->toAtomString() }}">{{ $idea->created_at->format('M j, Y') }}</time>
                </p>
            </div>

            {{-- ACTIONS --}}
            <div class="mt-3 flex flex-wrap items-center gap-3 border-t lk-si-border pt-3">
                <a href="{{ $opportunityUrl }}" class="btn-ghost text-sm">
                    <i data-lucide="external-link" class="h-4 w-4" aria-hidden="true"></i>
                    View original opportunity
                </a>
                <a href="{{ route('feedback.category', $idea->category) }}" class="btn-amber text-sm">
                    <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                    More {{ $idea->category }} ideas
                </a>
            </div>
        </article>

    @empty

        <div class="lk-card empty-state p-8 sm:p-10">
            <div class="empty-ico"><i data-lucide="bookmark" class="h-6 w-6" aria-hidden="true"></i></div>
            <p class="mt-3 font-semibold lk-si-text">No saved ideas yet.</p>
            <p class="mt-1 lk-si-copy lk-si-text3">
                Save ideas from capstone opportunities to track them here.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <a href="{{ route('capstone.opportunities') }}" class="btn-amber text-sm">Explore Opportunities</a>
                <a href="{{ route('discover') }}" class="btn-ghost text-sm">Browse Discover</a>
            </div>
        </div>

    @endforelse

</div>
@endsection
