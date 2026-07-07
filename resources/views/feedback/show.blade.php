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
                        <p class="mt-1 text-xs text-slate-500">{{ $feedback->created_at->diffForHumans() }} � {{ $feedback->cluster_name }}</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">#{{ Str::slug($feedback->category, '') }}</span>
                </div>
                <h1 class="mt-5 font-['Sora'] text-3xl font-extrabold text-slate-900 dark:text-white">{{ $feedback->title }}</h1>
                <p class="mt-4 text-sm leading-8 text-slate-600 dark:text-slate-300">{{ $feedback->description }}</p>

                @if ($feedback->attachment_path)
                    <div class="mt-5">
                        @if ($feedback->attachment_type === 'image')
                            <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ asset('storage/'.$feedback->attachment_path) }}" alt="Supporting evidence" class="max-h-72 rounded-[24px] border border-slate-200 object-cover dark:border-white/10">
                            </a>
                        @else
                            <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 dark:border-white/10 dark:text-slate-300">View attached evidence</a>
                        @endif
                    </div>
                @endif

                @if(isset($evidenceFiles) && $evidenceFiles->count())
                    <div class="mt-6">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Supporting Evidence</p>
                        <div class="mt-3 grid grid-cols-3 gap-3">
                            @foreach($evidenceFiles as $file)
                                <div class="rounded-lg border p-3 text-center bg-slate-50 dark:bg-white/5">
                                    @if($file->file_type === 'image')
                                        <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ asset('storage/'.$file->file_path) }}" alt="{{ $file->file_name }}" class="mx-auto max-h-36 object-cover rounded-md">
                                        </a>
                                    @else
                                        <div class="text-sm font-bold">📄 PDF</div>
                                    @endif
                                    <div class="mt-2 text-xs text-slate-600 dark:text-slate-300">{{ $file->file_name }}</div>
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
                        <button type="submit" class="rounded-full border border-amber-300 px-5 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-400/40 dark:text-amber-300 dark:hover:bg-amber-500/10">{{ $feedback->has_supported ? 'Supported' : 'Support Problem' }}</button>
                    </form>
                    <a href="{{ route('feedback.category', $feedback->category) }}" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-950">View generated ideas</a>
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
                        <textarea id="body" name="body" rows="4" class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-amber-300 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                        @enderror
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="rounded-full bg-amber-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200">Post evidence</button>
                        </div>
                    </form>
                @else
                    <div class="mt-6 rounded-[24px] border border-dashed border-slate-300 p-5 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">Sign in to add supporting experiences that strengthen this problem signal.</div>
                @endauth

                <div class="mt-6 space-y-4">
                    @forelse ($comments as $comment)
                        <article class="rounded-[24px] border border-slate-200 p-5 dark:border-white/10">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $comment->user?->name ?? 'Campus contributor' }}</p>
                                <p class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $comment->body }}</p>
                        </article>
                    @empty
                        <div class="rounded-[24px] border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">No supporting experiences yet. The first evidence entry can help validate how recurring this issue really is.</div>
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
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Severity</p>
                            <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $analysis['severity_level'] }}</p>
                            <p class="text-sm text-slate-500">{{ number_format($analysis['severity_score'], 1) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Confidence</p>
                            <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $analysis['confidence_level'] }}</p>
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
                <div class="mt-6 space-y-4">
                    @foreach ($analysis['timeline'] as $point)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span class="mt-1 h-3 w-3 rounded-full {{ $point['threshold_reached'] ? 'bg-amber-400' : ($point['idea_generated'] ? 'bg-sky-400' : 'bg-slate-300 dark:bg-slate-600') }}"></span>
                                @if (! $loop->last)
                                    <span class="mt-2 h-full w-px bg-slate-200 dark:bg-white/10"></span>
                                @endif
                            </div>
                            <div class="pb-4">
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $point['label'] }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $point['reports'] }} reports � {{ $point['supports'] }} supports</p>
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
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $problem->similarity }}% similar</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">{{ $problem->description }}</p>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">No additional clustered problems yet.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </section>
</div>
@endsection
