@extends('layouts.app')

@section('title', 'Home Feed')
@section('subtitle', 'Institutional problems, supporting evidence, and the signals guiding capstone discovery.')

@section('content')
@include('layouts.partials.design-system')

<div class="mx-auto max-w-7xl space-y-8">
    <section class="grid gap-4 lg:grid-cols-[1.6fr,1fr]">
        <div class="rounded-[28px] border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-slate-100 p-8 text-slate-950 shadow-xl shadow-amber-900/10 dark:border-white/10 dark:from-amber-500/15 dark:via-slate-950 dark:to-slate-900 dark:text-white dark:shadow-amber-950/20">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.35em] text-amber-700 dark:text-amber-200">LIKHA Feed</p>
            <h1 class="max-w-2xl font-['Sora'] text-3xl font-extrabold leading-tight md:text-5xl">Campus friction, made visible.</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-200/85">Every post below is an institutional problem. Support and evidence comments strengthen confidence, reveal recurring pain points, and help LIKHA surface capstone-worthy opportunities without changing the underlying decision logic.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('feedback.create') }}" class="btn-amber text-sm">Submit a Problem</a>
                <a href="{{ route('discover') }}" class="btn-ghost text-sm">Explore Discover</a>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-[24px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/75">
                <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Approved Problems</p>
                <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $totalProblems }}</p>
                <p class="mt-2 text-sm text-slate-500">Validated signals from the campus community.</p>
            </div>
            <div class="rounded-[24px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/75">
                <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Idea-Ready Categories</p>
                <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $ideaCandidates }}</p>
                <p class="mt-2 text-sm text-slate-500">Already approaching recommendation thresholds.</p>
            </div>
            <div class="rounded-[24px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/75">
                <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Tracked Hashtags</p>
                <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $totalCategories }}</p>
                <p class="mt-2 text-sm text-slate-500">Institutional domains visible in the feed.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-8 xl:grid-cols-[1.55fr,0.95fr]">
        <div class="space-y-4">
            @foreach ($feed as $feedback)
                <article class="lk-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->is_anonymous ? 'Anonymous contributor' : ($feedback->user?->name ?? 'Campus contributor') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $feedback->created_at->diffForHumans() }} • {{ $feedback->cluster_name }}</p>
                        </div>
                        <span class="shrink-0 badge-amber">#{{ Str::slug($feedback->category, '') }}</span>
                    </div>

                    <div class="mt-4 space-y-2">
                        <h2 class="font-['Sora'] text-lg font-bold text-slate-900 dark:text-white">{{ $feedback->title }}</h2>
                        <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $feedback->description }}</p>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="lk-badge badge-muted">Support {{ $feedback->votes_count }}</span>
                        <span class="lk-badge badge-muted">Evidence {{ $feedback->comments_count }}</span>
                        <span class="lk-badge badge-muted">Recurring {{ $feedback->recurring_report_count }}</span>
                        @php
                            $sevBadge = $feedback->severity_level === 'High' ? 'badge-red' : ($feedback->severity_level === 'Medium' ? 'badge-amber' : 'badge-green');
                            $conBadge = $feedback->confidence_level === 'High' ? 'badge-green' : ($feedback->confidence_level === 'Medium' ? 'badge-blue' : 'badge-muted');
                        @endphp
                        <span class="lk-badge {{ $sevBadge }}">Severity {{ number_format($feedback->severity_score, 1) }}</span>
                        <span class="lk-badge {{ $conBadge }}">Confidence {{ number_format($feedback->confidence_score, 1) }}</span>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/50 dark:border-white/10 pt-4">
                        <p class="max-w-2xl text-sm text-slate-500 dark:text-slate-400">{{ $feedback->why_it_matters }}</p>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
                                @csrf
                                <button type="submit" aria-label="{{ $feedback->has_supported ? 'Remove your support' : 'Support this problem' }}" aria-pressed="{{ $feedback->has_supported ? 'true' : 'false' }}" title="{{ $feedback->has_supported ? 'Supported' : 'Support this problem' }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $feedback->has_supported ? 'border-amber-500 bg-amber-100 text-amber-800 dark:border-amber-400 dark:bg-amber-500/20 dark:text-amber-200' : 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-500/10' }}">
                                    <i data-lucide="thumbs-up" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>{{ $feedback->votes_count }}</span>
                                </button>
                            </form>
                            <a href="{{ route('feedback.show', $feedback) }}" class="btn-amber text-sm">Open Problem</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <aside class="space-y-4">
            <div class="lk-card p-5">
                <div class="flex items-center justify-between">
                    <h3 class="font-['Sora'] text-lg font-bold text-slate-900 dark:text-white">Trending Problems</h3>
                    <a href="{{ route('discover', ['sort' => 'trending']) }}" class="text-sm font-semibold text-amber-600 dark:text-amber-300">View all</a>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach ($trending as $index => $problem)
                        <a href="{{ route('feedback.show', $problem) }}" class="block rounded-xl border border-slate-200/50 dark:border-white/10 p-4 transition hover:border-amber-300 hover:bg-amber-50/60 dark:hover:bg-white/5">
                            <div class="flex items-start gap-3">
                                <span class="font-['Sora'] text-lg font-bold text-amber-500">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $problem->title }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">{{ $problem->description }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        <span class="badge-amber">#{{ Str::slug($problem->category, '') }}</span>
                                        <span>{{ $problem->votes_count }} supports</span>
                                        <span>{{ $problem->recurring_report_count }} reports</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="lk-card p-5">
                <h3 class="font-['Sora'] text-lg font-bold text-slate-900 dark:text-white">Recently Generated Ideas</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($generatedIdeas as $idea)
                        <a href="{{ route('feedback.category', ['category' => $idea->category, 'idea' => $idea->idea_title]) }}" class="block rounded-xl border border-slate-200/50 dark:border-white/10 p-4 transition hover:border-sky-300 hover:bg-sky-50/60 dark:hover:bg-white/5">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ $idea->category }}</p>
                            <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $idea->idea_title }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Evaluation score {{ number_format($idea->overall_score, 2) }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">Generated capstone recommendations will appear here once thresholds are met.</p>
                    @endforelse
                </div>
            </div>

            <div class="lk-card p-5">
                <h3 class="font-['Sora'] text-lg font-bold text-slate-900 dark:text-white">Active Hashtags</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($categories->take(8) as $category)
                        <a href="{{ route('discover', ['category' => $category['name']]) }}" class="badge-amber text-sm">#{{ Str::slug($category['name'], '') }} <span class="ml-1 text-xs opacity-70">{{ $category['total'] }}</span></a>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection