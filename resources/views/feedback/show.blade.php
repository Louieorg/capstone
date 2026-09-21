@extends('layouts.app')

@section('title', $feedback->title)
@section('subtitle', 'Evidence-first problem view with related signals, analysis, and timeline context.')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="grid gap-8 xl:grid-cols-[1.45fr,0.8fr]">
        <div class="space-y-6">
            <article class="rounded-[30px] border border-black/5 bg-white/95 p-7 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->is_anonymous ? 'Anonymous contributor' : ($feedback->user?->name ?? 'Campus contributor') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $feedback->created_at->diffForHumans() }} &middot; {{ $feedback->cluster_name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">#{{ Str::slug($feedback->category, '') }}</span>
                        @if($feedback->severity_level)
                            <span class="rounded-full {{ $feedback->severity_level === 'High' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' : ($feedback->severity_level === 'Medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300') }} px-3 py-1 text-xs font-semibold">{{ $feedback->severity_level }} severity</span>
                        @endif
                        @if($feedback->confidence_level)
                            <span class="rounded-full {{ $feedback->confidence_level === 'High' ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-300' }} px-3 py-1 text-xs font-semibold">{{ $feedback->confidence_level }} confidence</span>
                        @endif
                    </div>
                </div>
                <h1 class="mt-5 font-['Sora'] text-3xl font-extrabold text-slate-900 dark:text-white">{{ $feedback->title }}</h1>

                @if($feedback->is_priority)
                <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-400/30 dark:bg-amber-500/10">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-amber-400 text-xs font-bold text-slate-900">
                        {{ $feedback->priority_office ? strtoupper(substr($feedback->priority_office, 0, 2)) : '!' }}
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-amber-700 dark:text-amber-300">Priority Problem &middot; {{ $feedback->public_priority_status }}</p>
                        @if($feedback->priority_office)
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Approach: {{ $feedback->priority_office }}</p>
                        @else
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Flagged as institutionally significant</p>
                        @endif
                    </div>
                </div>
                @endif

                <p class="mt-4 text-sm leading-8 text-slate-600 dark:text-slate-300">{{ $feedback->description }}</p>

                @if ($feedback->attachment_path)
                    <div class="mt-5">
                        @if ($feedback->attachment_type === 'image')
                            <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="block w-fit">
                                <img src="{{ asset('storage/'.$feedback->attachment_path) }}" alt="Supporting evidence" class="max-h-72 rounded-[24px] border border-slate-200 object-cover transition hover:opacity-90 dark:border-white/10">
                            </a>
                        @else
                            <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:text-slate-300">
                                <i data-lucide="file-text" class="h-4 w-4"></i>
                                View attached evidence
                            </a>
                        @endif
                    </div>
                @endif

                @if(isset($evidenceFiles) && $evidenceFiles->count())
                    <div class="mt-6">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Supporting Evidence</p>
                        <div class="mt-3 grid grid-cols-3 gap-3">
                            @foreach($evidenceFiles as $file)
                                <div class="rounded-lg border border-slate-200 p-3 text-center bg-slate-50 transition hover:border-amber-300 dark:border-white/10 dark:bg-white/5">
                                    @if($file->file_type === 'image')
                                        <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ asset('storage/'.$file->file_path) }}" alt="{{ $file->file_name }}" class="mx-auto max-h-36 object-cover rounded-md">
                                        </a>
                                    @else
                                        <div class="flex flex-col items-center gap-1 py-4 text-slate-500 dark:text-slate-400">
                                            <i data-lucide="file-text" class="h-6 w-6"></i>
                                            <span class="text-xs font-semibold">PDF</span>
                                        </div>
                                    @endif
                                    <div class="mt-2 truncate text-xs text-slate-600 dark:text-slate-300">{{ $file->file_name }}</div>
                                    @if($file->caption)
                                        <div class="mt-1 text-xs text-slate-500">{{ $file->caption }}</div>
                                    @endif
                                    <div class="mt-2 text-xs text-slate-400">Uploaded {{ $file->created_at->diffForHumans() }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">Support {{ $feedback->votes_count }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">Supporting experiences {{ $comments->count() }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-white/5 dark:text-slate-300">Recurring reports {{ $analysis['recurring_reports'] }}</span>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
                        @csrf
                        <button type="submit" aria-label="{{ $feedback->has_supported ? 'Remove your support' : 'Support this problem' }}" aria-pressed="{{ $feedback->has_supported ? 'true' : 'false' }}" title="{{ $feedback->has_supported ? 'Supported' : 'Support this problem' }}" class="inline-flex items-center gap-2 rounded-full border px-5 py-3 text-sm font-semibold transition {{ $feedback->has_supported ? 'border-amber-500 bg-amber-100 text-amber-800 dark:border-amber-400 dark:bg-amber-500/20 dark:text-amber-200' : 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-500/10' }}">
                            <i data-lucide="thumbs-up" class="h-4 w-4" aria-hidden="true"></i>
                            <span>{{ $feedback->votes_count }}</span>
                        </button>
                    </form>
                    <a href="{{ route('feedback.category', $feedback->category) }}" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-950">
                        View generated ideas
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            </article>

            <section class="rounded-[30px] border border-black/5 bg-white/95 p-7 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Supporting Evidence</p>
                        <h2 class="mt-2 font-['Sora'] text-2xl font-bold text-slate-900 dark:text-white">Community experiences</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $comments->count() }} entries</span>
                </div>

                @auth
                    <form method="POST" action="{{ route('feedback.comments.store', $feedback) }}" class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/5">
                        @csrf
                        <label for="body" class="text-sm font-semibold text-slate-900 dark:text-white">Add supporting experience</label>
                        <textarea id="body" name="body" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-amber-300 focus:ring-2 focus:ring-amber-100 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                        @enderror
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="rounded-full bg-amber-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200">Post evidence</button>
                        </div>
                    </form>
                @else
                    <div class="mt-6 flex items-center gap-3 rounded-[24px] border border-dashed border-slate-300 p-5 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">
                        <i data-lucide="lock" class="h-4 w-4 flex-shrink-0"></i>
                        Sign in to add supporting experiences that strengthen this problem signal.
                    </div>
                @endauth

                <div class="mt-6 space-y-4">
                    @forelse ($comments as $comment)
                        <article class="rounded-[24px] border border-slate-200 p-5 dark:border-white/10">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                    {{ strtoupper(substr($comment->user?->name ?? 'C', 0, 1)) }}
                                </div>
                                <div class="flex flex-1 items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $comment->user?->name ?? 'Campus contributor' }}</p>
                                    <p class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $comment->body }}</p>
                        </article>
                    @empty
                        <div class="flex items-start gap-3 rounded-[24px] border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">
                            <i data-lucide="message-circle" class="h-5 w-5 flex-shrink-0"></i>
                            No supporting experiences yet. The first evidence entry can help validate how recurring this issue really is.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">LIKHA Analysis</p>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Category</p>
                        <p class="mt-2 font-semibold text-slate-900 dark:text-white">#{{ Str::slug($analysis['category'], '') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl {{ $analysis['severity_level'] === 'High' ? 'bg-rose-50 dark:bg-rose-500/10' : ($analysis['severity_level'] === 'Medium' ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-emerald-50 dark:bg-emerald-500/10') }} p-4">
                            <p class="text-xs uppercase tracking-[0.18em] {{ $analysis['severity_level'] === 'High' ? 'text-rose-400' : ($analysis['severity_level'] === 'Medium' ? 'text-amber-500' : 'text-emerald-500') }}">Severity</p>
                            <p class="mt-2 font-semibold {{ $analysis['severity_level'] === 'High' ? 'text-rose-700 dark:text-rose-300' : ($analysis['severity_level'] === 'Medium' ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300') }}">{{ $analysis['severity_level'] }}</p>
                            <p class="text-sm text-slate-500">{{ number_format($analysis['severity_score'], 1) }}</p>
                        </div>
                        <div class="rounded-2xl {{ $analysis['confidence_level'] === 'High' ? 'bg-sky-50 dark:bg-sky-500/10' : 'bg-slate-50 dark:bg-white/5' }} p-4">
                            <p class="text-xs uppercase tracking-[0.18em] {{ $analysis['confidence_level'] === 'High' ? 'text-sky-400' : 'text-slate-400' }}">Confidence</p>
                            <p class="mt-2 font-semibold {{ $analysis['confidence_level'] === 'High' ? 'text-sky-700 dark:text-sky-300' : 'text-slate-900 dark:text-white' }}">{{ $analysis['confidence_level'] }}</p>
                            <p class="text-sm text-slate-500">{{ number_format($analysis['confidence_score'], 1) }}</p>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Affected groups</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ count($analysis['affected_groups']) ? implode(', ', $analysis['affected_groups']) : 'Not specified' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Evidence Files</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $analysis['evidence_files'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Why this matters</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $analysis['why_it_matters'] }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Timeline</p>
                        <h3 class="mt-2 font-['Sora'] text-xl font-bold text-slate-900 dark:text-white">Knowledge growth</h3>
                    </div>
                </div>
                <div class="mt-6">
                    @foreach ($analysis['timeline'] as $point)
                        <div class="flex gap-4 {{ ! $loop->last ? 'pb-4' : '' }}">
                            <div class="flex flex-col items-center self-stretch">
                                <span class="mt-1 h-3 w-3 flex-shrink-0 rounded-full {{ $point['threshold_reached'] ? 'bg-amber-400' : ($point['idea_generated'] ? 'bg-sky-400' : 'bg-slate-300 dark:bg-slate-600') }}"></span>
                                @if (! $loop->last)
                                    <span class="mt-2 w-px flex-1 bg-slate-200 dark:bg-white/10"></span>
                                @endif
                            </div>
                            <div class="min-w-0 pb-2">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $point['label'] }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $point['reports'] }} reports &middot; {{ $point['supports'] }} supports</p>
                                @if ($point['threshold_reached'])
                                    <p class="mt-2 text-sm font-semibold text-amber-600 dark:text-amber-300">Threshold reached for recommendation readiness.</p>
                                @elseif ($point['idea_generated'])
                                    <p class="mt-2 text-sm font-semibold text-sky-600 dark:text-sky-300">LIKHA has enough evidence to sustain capstone generation.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-['Sora'] text-xl font-bold text-slate-900 dark:text-white">Related Problems</h3>
                    <span class="text-sm text-slate-500">Based on clustering</span>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($relatedProblems as $problem)
                        <a href="{{ route('feedback.show', $problem) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/60 dark:border-white/10 dark:hover:bg-white/5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $problem->title }}</p>
                                <span class="flex-shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $problem->similarity }}% similar</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">{{ $problem->description }}</p>
                        </a>
                    @empty
                        <div class="flex items-start gap-3 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">
                            <i data-lucide="git-branch" class="h-4 w-4 flex-shrink-0"></i>
                            No additional clustered problems yet.
                        </div>
                    @endforelse
                </div>
            </section>
        </aside>
    </section>
</div>
@endsection