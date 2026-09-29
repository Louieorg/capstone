@extends('layouts.app')

@section('title', 'My Contribution')
@section('subtitle', 'Your institutional contribution and your account settings.')

@section('content')
@include('layouts.partials.design-system')

@php
    $contributionMetrics = [
        [
            'label' => 'Problems Submitted',
            'value' => $submittedProblems,
            'icon' => 'message-square',
        ],
        [
            'label' => 'Problems Supported',
            'value' => $supportedProblems,
            'icon' => 'thumbs-up',
        ],
        [
            'label' => 'Evidence Entries',
            'value' => $evidenceContributions,
            'icon' => 'file-text',
        ],
        [
            'label' => 'Ideas Contributed To',
            'value' => $generatedIdeasContributedTo,
            'icon' => 'lightbulb',
        ],
    ];
@endphp

<div class="mx-auto max-w-5xl space-y-6">

    {{-- ══ CONTRIBUTION SUMMARY ══ --}}
    <section class="lk-card anim-1 p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-['Sora'] text-lg font-bold leading-snug text-slate-900 dark:text-white">
                    Contribution Summary
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    LIKHA profiles measure institutional contribution, not social reach. Your reports, support
                    signals, and evidence entries are what shape the recommendations LIKHA produces.
                </p>
            </div>

            <span class="lk-badge badge-muted shrink-0">
                <i data-lucide="user-round" class="h-3 w-3" aria-hidden="true"></i>
                {{ ucfirst($user->role ?? 'User') }}
            </span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-5 sm:grid-cols-3">
            @foreach ($contributionMetrics as $metric)
                <div class="flex min-w-0 flex-col gap-1.5">
                    <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <i data-lucide="{{ $metric['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                        {{ $metric['label'] }}
                    </span>
                    <span class="font-['Sora'] text-2xl font-bold leading-tight text-slate-900 dark:text-white">
                        {{ $metric['value'] }}
                    </span>
                </div>
            @endforeach

            {{-- Contribution score: the single emphasised metric --}}
            <div class="col-span-2 flex min-w-0 flex-col gap-1.5">
                <span class="lk-badge badge-amber w-fit">
                    <i data-lucide="award" class="h-3 w-3" aria-hidden="true"></i>
                    Contribution Score
                </span>
                <span class="font-['Sora'] text-2xl font-bold leading-tight" style="color: var(--amber);">
                    {{ $communityContributionScore }}
                </span>
            </div>
        </div>

        <p class="mt-5 border-t border-slate-200 pt-4 text-xs leading-6 text-slate-500 dark:border-white/10 dark:text-slate-400">
            Your score grows when you document real problems, validate them with support, and add evidence that
            makes those recommendations more trustworthy.
        </p>
    </section>

    {{-- ══ ACCOUNT SETTINGS ══ --}}
    <section class="lk-card anim-2 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6 dark:border-white/10">
            <h2 class="font-['Sora'] text-lg font-bold leading-snug text-slate-900 dark:text-white">
                Account Settings
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Manage your sign-in details, password, and account access.
            </p>
        </div>

        <div class="p-5 sm:p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="border-t border-slate-200 p-5 sm:p-6 dark:border-white/10">
            @include('profile.partials.update-password-form')
        </div>

        <div class="border-t border-slate-200 p-5 sm:p-6 dark:border-white/10">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
</div>
@endsection
