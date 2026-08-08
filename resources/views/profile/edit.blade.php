@extends('layouts.app')

@section('title', 'My Contribution')
@section('subtitle', 'Profile settings and the impact of your reports, support signals, and evidence entries.')

@section('content')
<div class="mx-auto max-w-7xl space-y-8">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-[28px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Problems Submitted</p>
            <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $submittedProblems }}</p>
        </div>
        <div class="rounded-[28px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Problems Supported</p>
            <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $supportedProblems }}</p>
        </div>
        <div class="rounded-[28px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Evidence Entries</p>
            <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $evidenceContributions }}</p>
        </div>
        <div class="rounded-[28px] border border-black/5 bg-white/95 p-5 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Ideas Contributed To</p>
            <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $generatedIdeasContributedTo }}</p>
        </div>
        <div class="rounded-[28px] border border-amber-200 bg-amber-50/90 p-5 shadow-lg shadow-amber-900/10 dark:border-amber-400/25 dark:bg-amber-500/10">
            <p class="text-xs uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Contribution Score</p>
            <p class="mt-3 font-['Sora'] text-4xl font-bold text-slate-900 dark:text-white">{{ $communityContributionScore }}</p>
        </div>
    </section>

    <section class="grid gap-8 xl:grid-cols-[0.9fr,1.1fr]">
        <div class="rounded-[30px] border border-black/5 bg-white/95 p-7 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Profile Focus</p>
    <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">LIKHA profiles are about institutional contribution rather than social reach. Your score increases when you document real problems, validate them with support, and add evidence that makes the system's recommendations more trustworthy.</p>
    <div class="mt-6 space-y-3">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Primary role</p>
                    <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ ucfirst($user->role ?? 'User') }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Contribution mix</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $submittedProblems }} reports, {{ $supportedProblems }} support signals, and {{ $evidenceContributions }} evidence entries are currently shaping LIKHA outputs.</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                @include('profile.partials.update-password-form')
            </div>
            <div class="rounded-[30px] border border-black/5 bg-white/95 p-6 shadow-lg shadow-slate-900/5 dark:border-white/10 dark:bg-slate-950/80">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>
</div>
@endsection
