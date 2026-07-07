@extends('layouts.app')

@section('title', 'Discover')
@section('subtitle', 'Browse institutional problems by momentum, support, severity, and fresh capstone activity.')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="grid gap-4 xl:grid-cols-[1.35fr,0.65fr]">
        <form method="GET" action="{{ route('discover') }}" class="rounded-[28px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <div class="grid gap-4 md:grid-cols-[1.5fr,1fr,auto]">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Queue delays, registrar, Wi-Fi…" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-amber-300 focus:bg-white dark:border-white/10 dark:bg-white/5 dark:text-slate-100" />
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Hashtag</label>
                    <select name="category" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-amber-300 focus:bg-white dark:border-white/10 dark:bg-white/5 dark:text-slate-100">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-950">Apply</button>
                    @if (request()->hasAny(['search', 'category', 'sort']))
                        <a href="{{ route('discover') }}" class="text-sm font-semibold text-slate-500 dark:text-slate-400">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="rounded-[28px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Recently Generated Ideas</p>
            <div class="mt-4 space-y-3">
                @forelse ($recentIdeas as $idea)
                    <a href="{{ route('feedback.category', ['category' => $idea->category, 'idea' => $idea->idea_title]) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/60 dark:border-white/10 dark:hover:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $idea->category }}</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $idea->idea_title }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Overall score {{ number_format($idea->overall_score, 2) }}</p>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No generated ideas yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="flex flex-wrap gap-2">
        @php
            $sorts = [
                'trending' => 'Trending',
                'newest' => 'Newest',
                'supported' => 'Most Supported',
                'severity' => 'Highest Severity',
            ];
        @endphp
        @foreach ($sorts as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['sort' => $value, 'page' => null]) }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeSort === $value ? 'bg-amber-300 text-slate-950' : 'bg-white/90 text-slate-600 shadow-sm dark:bg-slate-950/70 dark:text-slate-300' }}">{{ $label }}</a>
        @endforeach
    </section>

    <section class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($feedbacks as $feedback)
            <article class="rounded-[28px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 transition hover:-translate-y-1 hover:shadow-xl dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $feedback->created_at->diffForHumans() }} • {{ $feedback->user?->name ?? 'Anonymous contributor' }}</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">#{{ Str::slug($feedback->category, '') }}</span>
                </div>
                <p class="mt-4 line-clamp-4 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $feedback->description }}</p>
                <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $feedback->votes_count }} support</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $feedback->comments_count }} evidence</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $feedback->recurring_report_count }} recurring</span>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Severity</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $feedback->severity_level }} • {{ number_format($feedback->severity_score, 1) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Confidence</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $feedback->confidence_level }} • {{ number_format($feedback->confidence_score, 1) }}</p>
                    </div>
                </div>
                <p class="mt-5 text-sm text-slate-500 dark:text-slate-400">{{ $feedback->why_it_matters }}</p>
                <div class="mt-6 flex items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-white/10">
                    <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-500/10">{{ $feedback->has_supported ? 'Supported' : 'Support Problem' }}</button>
                    </form>
                    <a href="{{ route('feedback.show', $feedback) }}" class="text-sm font-semibold text-slate-900 dark:text-white">View details</a>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-[28px] border border-dashed border-slate-300 bg-white/90 p-10 text-center text-slate-500 dark:border-white/15 dark:bg-slate-950/75 dark:text-slate-400">No problems matched the current filters.</div>
        @endforelse
    </section>

    @if ($feedbacks->hasPages())
        <div class="pt-2">{{ $feedbacks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
