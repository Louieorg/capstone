@extends('layouts.app')

@section('title', 'Capstone Opportunities')
@section('subtitle', 'Institutional problems already identified as potential capstone projects.')

@section('content')
@include('layouts.partials.design-system')

<div class="mx-auto max-w-7xl space-y-6">

    <section class="grid gap-6 2xl:grid-cols-[1.7fr,0.6fr]">

        {{-- ══ IDENTIFIED OPPORTUNITIES ══ --}}
        <div class="space-y-4">
            @forelse ($opportunities as $opportunity)
                @php
                    $identifiedAt = $opportunity->capstone_marked_at
                        ? \Illuminate\Support\Carbon::parse($opportunity->capstone_marked_at)
                        : null;
                @endphp

                <article class="lk-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 space-y-2">
                            <span class="lk-badge badge-green">
                                <i data-lucide="lightbulb" class="h-3 w-3" aria-hidden="true"></i>
                                Capstone Opportunity
                            </span>
                            <h2 class="font-['Sora'] text-lg font-bold leading-snug text-slate-900 dark:text-white">{{ $opportunity->title }}</h2>
                        </div>
                        <span class="shrink-0 badge-amber">{{ $opportunity->category }}</span>
                    </div>

                    <div class="mt-3 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                        @if ($opportunity->department || $opportunity->priority_office)
                            <p class="flex flex-wrap gap-x-3 gap-y-1">
                                @if ($opportunity->department)
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="building-2" class="h-3 w-3"></i>
                                        Department: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $opportunity->department }}</span>
                                    </span>
                                @endif
                                @if ($opportunity->priority_office)
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="flag" class="h-3 w-3"></i>
                                        Priority office: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $opportunity->priority_office }}</span>
                                    </span>
                                @endif
                            </p>
                        @endif
                        <p class="flex items-center gap-1.5">
                            <i data-lucide="calendar" class="h-3 w-3"></i>
                            Identified {{ $identifiedAt ? $identifiedAt->diffForHumans() : 'recently' }}
                            by {{ $opportunity->capstoneMarkedBy?->name ?? 'an institutional office' }}
                        </p>
                    </div>

                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $opportunity->description }}</p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="lk-badge badge-muted">{{ $opportunity->votes_count }} support</span>
                        <span class="lk-badge badge-muted">{{ $opportunity->comments_count }} evidence</span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-200/50 dark:border-white/10 pt-3">
                        <a href="{{ route('feedback.show', $opportunity) }}" class="btn-ghost text-sm">View Problem</a>
                        <a href="{{ route('feedback.category', $opportunity->category) }}" class="btn-amber text-sm">
                            <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                            Explore DSS Ideas
                        </a>
                    </div>
                </article>
            @empty
                <div class="lk-card p-10 text-center empty-state">
                    <div class="empty-ico"><i data-lucide="lightbulb" class="h-6 w-6"></i></div>
                    <p class="mt-3 font-semibold text-slate-900 dark:text-white">No capstone opportunities identified yet.</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Institutional offices mark approved problems as capstone opportunities once the evidence is strong enough.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('discover') }}" class="btn-ghost text-sm">Browse Discover</a>
                        <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a Problem</a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ══ DSS IDEAS BY CATEGORY ══ --}}
        <div>
            <div class="lk-card p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">DSS Ideas by Category</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Already generated by LIKHA from problems that passed the DSS thresholds.
                </p>

                <div class="mt-3 space-y-2">
                    @forelse ($dssIdeasByCategory as $entry)
                        <a href="{{ route('feedback.category', $entry->category) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/50 dark:border-white/10 px-3 py-2 transition hover:border-amber-300 hover:bg-amber-50/60 dark:hover:bg-white/5">
                            <span class="min-w-0 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $entry->category }}</span>
                            <span class="shrink-0 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $entry->total }} {{ Str::plural('idea', $entry->total) }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">No DSS ideas generated yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    @if ($opportunities->hasPages())
        <div class="pt-2">{{ $opportunities->withQueryString()->links() }}</div>
    @endif
</div>
@endsection