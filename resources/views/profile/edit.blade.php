@extends('layouts.app')

@section('title', 'My Contribution')
@section('subtitle', 'Profile settings and the impact of your reports, support signals, and evidence entries.')

@section('content')
@include('layouts.partials.design-system')

<div class="mx-auto max-w-7xl space-y-8">
    <section class="stat-grid anim-1">
        <div class="s-card">
            <div class="s-icon">
                <i data-lucide="message-square" style="width:16px;height:16px;"></i>
            </div>
            <div class="s-label">Problems Submitted</div>
            <div class="s-val">{{ $submittedProblems }}</div>
        </div>

        <div class="s-card">
            <div class="s-icon blue">
                <i data-lucide="thumbs-up" style="width:16px;height:16px;"></i>
            </div>
            <div class="s-label">Problems Supported</div>
            <div class="s-val">{{ $supportedProblems }}</div>
        </div>

        <div class="s-card">
            <div class="s-icon green">
                <i data-lucide="file-text" style="width:16px;height:16px;"></i>
            </div>
            <div class="s-label">Evidence Entries</div>
            <div class="s-val">{{ $evidenceContributions }}</div>
        </div>

        <div class="s-card">
            <div class="s-icon" style="background:var(--blue-bg);border-color:var(--blue-b);color:var(--blue)">
                <i data-lucide="lightbulb" style="width:16px;height:16px;"></i>
            </div>
            <div class="s-label">Ideas Contributed To</div>
            <div class="s-val">{{ $generatedIdeasContributedTo }}</div>
        </div>

        <div class="s-card featured">
            <div class="s-icon">
                <i data-lucide="award" style="width:16px;height:16px;"></i>
            </div>
            <div class="s-label">Contribution Score</div>
            <div class="s-val">{{ $communityContributionScore }}</div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr,1.1fr] anim-2">
        <div class="lk-card p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Profile Focus</p>
            <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">LIKHA profiles are about institutional contribution rather than social reach. Your score increases when you document real problems, validate them with support, and add evidence that makes the system's recommendations more trustworthy.</p>
            <div class="mt-6 space-y-3">
                <div class="lk-card p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Primary role</p>
                    <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ ucfirst($user->role ?? 'User') }}</p>
                </div>
                <div class="lk-card p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Contribution mix</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $submittedProblems }} reports, {{ $supportedProblems }} support signals, and {{ $evidenceContributions }} evidence entries are currently shaping LIKHA outputs.</p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="lk-card p-6">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="lk-card p-6">
                @include('profile.partials.update-password-form')
            </div>
            <div class="lk-card p-6">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>
</div>
@endsection