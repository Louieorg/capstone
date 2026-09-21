@extends('layouts.app')

@section('title','My Saved Ideas')

@section('content')
@include('layouts.partials.design-system')

<div class="mx-auto max-w-5xl px-4 sm:px-6 space-y-6">

    {{-- HEADER --}}
    <header class="anim-1">
        <h2 class="font-['Sora'] text-2xl font-bold text-slate-900 dark:text-white">
            My Saved Ideas
        </h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Review and compare your saved capstone ideas
        </p>
    </header>

    @forelse($ideas as $idea)

    @php
        $responsibleOffice = isset($responsibleOffices) ? $responsibleOffices->get($idea->category) : null;
        $opportunityUrl = route('feedback.category', ['category' => $idea->category, 'idea' => $idea->title]).'#idea-'.Str::slug($idea->title);
        $evaluation = $idea->ideaEvaluation;
    @endphp

    <article class="lk-card p-5 sm:p-6">
        {{-- TOP: Title + Category + Status --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
            <div class="flex-1 min-w-0">
                <h3 class="font-['Sora'] text-lg font-semibold text-slate-900 dark:text-white truncate">
                    {{ $idea->title }}
                </h3>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <span class="lk-badge badge-amber">{{ $idea->category }}</span>

                <form method="POST" action="{{ route('idea.updateStatus', $idea->id) }}" class="shrink-0">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                        class="lk-input text-sm py-1.5 px-3 cursor-pointer"
                        aria-label="Update idea status">
                        @foreach(['Exploring', 'Adopted', 'In Progress', 'Completed'] as $s)
                            <option value="{{ $s }}" {{ $idea->status === $s ? 'selected' : '' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- DESCRIPTION --}}
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 leading-relaxed">
            {{ $idea->description }}
        </p>

        {{-- CONTEXT METADATA --}}
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mb-4">
            @if($responsibleOffice)
                <span class="flex items-center gap-1.5">
                    <i data-lucide="building-2" class="h-3 w-3"></i>
                    <span>Responsible office:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $responsibleOffice }}</span>
                </span>
            @endif

            @if($evaluation)
                <span class="flex items-center gap-1.5">
                    <i data-lucide="bar-chart-2" class="h-3 w-3"></i>
                    <span>DSS evaluation:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format((float) $evaluation->overall_score, 2) }}</span>
                    @if($evaluation->recommendation)
                        <span class="lk-badge badge-blue">{{ $evaluation->recommendation }}</span>
                    @endif
                </span>
            @endif

            <span class="flex items-center gap-1.5">
                <i data-lucide="calendar" class="h-3 w-3"></i>
                <span>Saved {{ $idea->created_at->diffForHumans() }}</span>
            </span>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-200/50 dark:border-white/10">
            <a href="{{ $opportunityUrl }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 dark:text-amber-300 hover:underline">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                View original opportunity
            </a>
        </div>
    </article>

    @empty

    <div class="lk-card p-8 text-center empty-state">
        <div class="empty-ico"><i data-lucide="bookmark" class="h-6 w-6"></i></div>
        <p class="empty-text">No saved ideas yet.</p>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Save ideas from capstone opportunities to track them here.</p>
        <a href="{{ route('capstone.opportunities') }}" class="mt-4 inline-flex btn-amber">Explore Opportunities</a>
    </div>

    @endforelse

</div>
@endsection