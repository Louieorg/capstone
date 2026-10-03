@extends('layouts.app')

@section('title', 'My Contribution')
@section('subtitle', 'Your institutional contribution and your account settings.')

@section('content')
@include('layouts.partials.design-system')

@php
    // Representation is a relationship on the active Office rows, not a role.
    // One query feeds the position label and the represented office names.
    $representedOffices = $user->activeRepresentedOffices();
    $representedOfficeNames = $user->role === 'user' ? $representedOffices->pluck('name') : collect();
    $positionLabel = $representedOfficeNames->isNotEmpty()
        ? 'Office Representative'
        : ucfirst($user->role ?? 'User');
@endphp

<style>
    /* ── Mobile personal hub (below 768px) ──
       Profile is a permanent bottom-nav destination there, so this hub carries
       the personal and account actions that the desktop sidebar account control
       provides. Token-only, one flat surface, no nested cards. */
    .pf-hub {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .pf-identity {
        display: flex; align-items: center; gap: 12px;
        padding: 16px 16px; border-bottom: 1px solid var(--border);
    }
    .pf-identity .h-avatar { width: 42px; height: 42px; font-size: 16px; }
    .pf-identity-text { min-width: 0; }
    .pf-identity-name {
        font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
        color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pf-identity-role {
        font-size: 10px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
        color: var(--muted); margin-top: 2px;
    }
    .pf-identity-office {
        font-size: 11.5px; color: var(--text2); margin-top: 2px;
        overflow-wrap: break-word; min-width: 0;
    }

    .pf-group { border-bottom: 1px solid var(--border); }
    .pf-group-label {
        padding: 14px 16px 6px;
        font-size: 10px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
        color: var(--text3);
    }
    .pf-row {
        display: flex; align-items: center; gap: 12px; width: 100%;
        padding: 13px 16px;
        border: 0; border-top: 1px solid var(--border); border-radius: 0;
        background: transparent; text-align: left; text-decoration: none;
        color: var(--text); font-size: 13.5px; font-weight: 500;
        font-family: 'DM Sans', sans-serif; cursor: pointer;
        transition: background .15s;
    }
    .pf-row:first-of-type { border-top: 0; }
    .pf-row:hover, .pf-row:focus-visible { background: var(--amber-dim); }
    .pf-row:focus-visible { outline: 2px solid var(--amber); outline-offset: -2px; }
    .pf-row-icon { width: 17px; height: 17px; flex-shrink: 0; color: var(--muted); }
    .pf-row:hover .pf-row-icon { color: var(--amber); }
    .pf-row-text { flex: 1; min-width: 0; }
    .pf-row-chevron { width: 15px; height: 15px; flex-shrink: 0; color: var(--muted2); }

    /* One icon reflects the active theme; no JS needed to read it. */
    .pf-theme-dark { display: none; }
    html.dark .pf-theme-light { display: none; }
    html.dark .pf-theme-dark { display: inline; }

    /* Logout: separated, subdued, and clearly the destructive one. */
    .pf-logout {
        display: flex; align-items: center; gap: 12px; width: 100%;
        margin: 14px 0 0; padding: 13px 16px;
        border: 1px solid var(--border); border-radius: 10px;
        background: transparent; color: var(--red);
        font-size: 13.5px; font-weight: 600; text-align: left;
        font-family: 'DM Sans', sans-serif; cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .pf-logout:hover { background: var(--red-bg); border-color: var(--red-b); }
    .pf-logout i { width: 17px; height: 17px; flex-shrink: 0; }
</style>

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

    {{-- ══ MOBILE PERSONAL HUB ══
         Only below 768px, where the sidebar is hidden and Profile is a bottom-nav
         destination. It re-exposes the personal and account actions that the
         desktop sidebar account control owns, and links to the same existing
         routes. Saved Ideas stays its own page. --}}
    <section class="pf-hub md:hidden" aria-label="Profile">
        <div class="pf-identity">
            <span class="h-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div class="pf-identity-text">
                <div class="pf-identity-name">{{ $user->name }}</div>
                <div class="pf-identity-role">{{ $positionLabel }}</div>
                @foreach($representedOfficeNames as $representedOfficeName)
                    <div class="pf-identity-office">{{ $representedOfficeName }}</div>
                @endforeach
            </div>
        </div>

        <div class="pf-group">
            <div class="pf-group-label">My LIKHA</div>
            <a href="{{ route('user.ideas') }}" class="pf-row">
                <i data-lucide="bookmark" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">Saved Ideas</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
            {{-- Mobile counterpart of the sidebar item, so a representative can
                 reach their queue on a phone too. --}}
            @if($user->isOfficeRepresentative())
                <a href="{{ route('office.confirmations.index') }}" class="pf-row">
                    <i data-lucide="clipboard-check" class="pf-row-icon" aria-hidden="true"></i>
                    <span class="pf-row-text">Confirmation Requests</span>
                    <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
                </a>
            @endif
            <a href="{{ route('profile.edit') }}#profile-contribution" class="pf-row">
                <i data-lucide="badge-check" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">My Contribution</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
        </div>

        <div class="pf-group">
            <div class="pf-group-label">Account</div>
            <a href="{{ route('profile.edit') }}#profile-information" class="pf-row">
                <i data-lucide="user-round" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">Profile Information</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
            <a href="{{ route('profile.edit') }}#profile-password" class="pf-row">
                <i data-lucide="key-round" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">Change Password</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
        </div>

        <div class="pf-group">
            <div class="pf-group-label">Help &amp; Settings</div>
            <a href="{{ route('help.user-guide') }}" class="pf-row">
                <i data-lucide="book-open" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">User Guide</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
            <a href="{{ route('help.faq') }}" class="pf-row">
                <i data-lucide="circle-help" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">FAQ</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
            <a href="{{ route('help.privacy') }}" class="pf-row">
                <i data-lucide="shield-check" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">Privacy Rights</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
            <a href="{{ route('terms') }}" class="pf-row">
                <i data-lucide="file-text" class="pf-row-icon" aria-hidden="true"></i>
                <span class="pf-row-text">Terms &amp; Conditions</span>
                <i data-lucide="chevron-right" class="pf-row-chevron" aria-hidden="true"></i>
            </a>
        </div>

        <div class="pf-group">
            <div class="pf-group-label">Preferences</div>
            <button type="button" class="pf-row" onclick="
                const d=document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme',d?'dark':'light')">
                <i data-lucide="sun" class="pf-row-icon pf-theme-light" aria-hidden="true"></i>
                <i data-lucide="moon" class="pf-row-icon pf-theme-dark" aria-hidden="true"></i>
                <span class="pf-row-text">Light / Dark Mode</span>
            </button>
        </div>

        <div style="padding: 0 0 14px">
            <button type="button" class="pf-logout" style="margin: 0 16px" onclick="confirmLogout()">
                <i data-lucide="log-out" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </div>
    </section>

    {{-- ══ CONTRIBUTION SUMMARY ══ --}}
    <section class="lk-card anim-1 p-5 sm:p-6" id="profile-contribution">
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
                {{ $positionLabel }}
            </span>
            @if($representedOfficeNames->isNotEmpty())
                <span class="lk-badge badge-amber shrink-0">
                    <i data-lucide="building-2" class="h-3 w-3" aria-hidden="true"></i>
                    {{ $representedOfficeNames->join(', ') }}
                </span>
            @endif
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

        <div class="p-5 sm:p-6" id="profile-information">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="border-t border-slate-200 p-5 sm:p-6 dark:border-white/10" id="profile-password">
            @include('profile.partials.update-password-form')
        </div>

        <div class="border-t border-slate-200 p-5 sm:p-6 dark:border-white/10">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
</div>
@endsection
