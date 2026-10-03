@extends('layouts.app')

@section('title', 'User Guide')
@section('subtitle', 'How LIKHA works, from reporting a problem to building on an opportunity')

@section('content')
@include('layouts.partials.design-system')

{{-- User Guide: documentation only. Styles are scoped to this page so the
     global shell stays untouched. --}}
<style>
    .ug-intro {
        border: 1px solid var(--border);
        border-left: 3px solid var(--amber);
        border-radius: 12px;
        background: var(--amber-dim);
        padding: 16px 18px;
    }
    .ug-intro p {
        margin: 0; font-size: 13.5px; line-height: 1.7; color: var(--text2);
    }

    .ug-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        scroll-margin-top: 20px;
    }

    .ug-head {
        display: flex; align-items: center; gap: 10px; margin-bottom: 4px;
    }
    .ug-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; flex-shrink: 0;
        border-radius: 8px;
        background: var(--amber-dim); border: 1px solid var(--amber-mid);
        color: var(--amber); font-size: 12px; font-weight: 700;
    }
    .ug-title {
        font-family: 'Sora', sans-serif; font-size: 15.5px; font-weight: 700;
        color: var(--text); margin: 0;
    }
    .ug-lede { margin: 0 0 14px; font-size: 12.5px; color: var(--text3); line-height: 1.6; }

    .ug-items { display: flex; flex-direction: column; gap: 12px; }

    .ug-item {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 13px 15px;
        background: var(--surface2);
    }
    .ug-item-head {
        display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
        font-size: 13px; font-weight: 600; color: var(--text);
    }
    .ug-item-head i[data-lucide] { width: 15px; height: 15px; color: var(--amber); flex-shrink: 0; }
    .ug-item p { margin: 0; font-size: 12.5px; line-height: 1.7; color: var(--text2); }

    .ug-defs { display: flex; flex-direction: column; gap: 8px; }
    .ug-def {
        display: grid; grid-template-columns: 168px minmax(0, 1fr);
        gap: 4px 14px; align-items: baseline;
    }
    @media (max-width: 640px) {
        .ug-def { grid-template-columns: minmax(0, 1fr); }
    }
    .ug-def dt {
        font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        color: var(--text3);
    }
    .ug-def dd { margin: 0; font-size: 12.5px; line-height: 1.7; color: var(--text2); }

    .ug-flow { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .ug-step {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 12px; border-radius: 999px;
        border: 1px solid var(--border); background: var(--surface2);
        font-size: 12px; font-weight: 600; color: var(--text);
    }
    .ug-step i[data-lucide] { width: 14px; height: 14px; color: var(--amber); }
    .ug-arrow { color: var(--text3); font-size: 13px; }

    .ug-note {
        margin-top: 14px; padding: 12px 14px;
        border: 1px solid var(--border); border-radius: 10px;
        background: var(--surface2);
        font-size: 12.5px; line-height: 1.7; color: var(--text2);
    }
    .ug-note strong { color: var(--text); }
    .ug-note-caution { border-color: var(--amber-mid); background: var(--amber-dim); }

    /* Final principle block */
    .ug-principle {
        border: 1px solid var(--amber-mid);
        border-radius: 16px;
        background: var(--surface);
        padding: 26px 24px;
        text-align: center;
    }
    .ug-principle-line {
        font-family: 'Sora', sans-serif;
        font-size: clamp(19px, 3.2vw, 28px);
        font-weight: 800; letter-spacing: .01em; line-height: 1.25;
        margin: 0;
    }
    .ug-principle-dss { color: var(--text); }
    .ug-principle-ai { color: var(--amber); }
    .ug-principle-sub {
        margin: 14px auto 0; max-width: 620px;
        font-size: 12.5px; line-height: 1.75; color: var(--text2);
    }
</style>

<div class="mx-auto max-w-4xl" style="display:flex;flex-direction:column;gap:16px">

    {{-- Intro --}}
    <div class="ug-intro anim-1">
        <p>
            LIKHA is a Decision Support System for institutional problems and capstone opportunities.
            This guide explains how the pieces fit together, what each piece means, and where your
            own actions fit. Nothing here changes how the system works — it only describes it.
        </p>
    </div>

    {{-- 1. Getting started --}}
    <section class="ug-section anim-1" id="ug-getting-started">
        <div class="ug-head">
            <span class="ug-num">1</span>
            <h2 class="ug-title">Getting started</h2>
        </div>
        <p class="ug-lede">The eight places you will use, and what each one is for.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="layout-dashboard" aria-hidden="true"></i>Home Feed</div>
                <p>Your starting point. It orients you: what is happening on campus, which problems are latest, and which DSS ideas have recently been generated.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="compass" aria-hidden="true"></i>Discover</div>
                <p>Where you browse institutional problems. Filter by category, search by keyword, and open any problem to read its full details and supporting evidence.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="plus-circle" aria-hidden="true"></i>Submit Problem</div>
                <p>Where you report an institutional problem you have actually encountered. See section 2 for what makes a good report.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="lightbulb" aria-hidden="true"></i>Capstone Opportunities</div>
                <p>The qualified opportunities you can build on, split into office-backed opportunities and community-generated ideas. See sections 4 and 7.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="bookmark" aria-hidden="true"></i>Saved Ideas</div>
                <p>Your personal bookmarks of generated ideas. Personal only — see section 6 for what saving does and does not do.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="badge-check" aria-hidden="true"></i>My Contribution</div>
                <p>Your profile and account: your contribution summary, profile information, password, theme and logout. See section 11.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="compass" aria-hidden="true"></i>Discover categories</div>
                <p>Each institutional problem sits in a category. Opening a category shows the problems inside it and the ideas the DSS generated from them.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="user-round" aria-hidden="true"></i>Profile</div>
                <p>On a phone this is your personal hub, holding the same account actions plus saved ideas and, for office representatives, your confirmation requests.</p>
            </div>
        </div>
    </section>

    {{-- 2. Submitting an institutional problem --}}
    <section class="ug-section anim-1" id="ug-submitting">
        <div class="ug-head">
            <span class="ug-num">2</span>
            <h2 class="ug-title">Submitting an institutional problem</h2>
        </div>
        <p class="ug-lede">A report describes a real, recurring problem you have evidence for — not a project idea.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="clipboard-list" aria-hidden="true"></i>What counts as an institutional problem</div>
                <p>A recurring obstacle in how the institution works: a process that is slow or unclear, a record that is never kept, a service that is hard to access. If it happens repeatedly and affects people beyond yourself, it is a candidate.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="pencil-line" aria-hidden="true"></i>Documenting it</div>
                <p>Describe what happens today, how often it happens, who it affects, and what the impact is. The more concrete the description, the better it can be evaluated later.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="paperclip" aria-hidden="true"></i>Supporting evidence</div>
                <p>You may attach files as evidence when you submit. Evidence is reviewed alongside the report and helps establish that the problem is real.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="users" aria-hidden="true"></i>Community submission</div>
                <p>A report you submit as yourself enters review as a community report. It becomes visible to others only after it is approved.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="building-2" aria-hidden="true"></i>Submitting on behalf of an office</div>
                <p>If you currently represent an office, the submission form lets you select that office. The report is then filed as an institutional office report and is qualified for that office without waiting on the usual community thresholds.</p>
            </div>
        </div>

        <div class="ug-note">
            <strong>You can only select an office you currently represent.</strong>
            If you no longer represent an office, that option is no longer offered.
        </div>

        <div class="ug-note">
            <strong>Duplicate detection.</strong>
            If your report closely resembles an existing problem, LIKHA shows the similar reports and
            lets you choose either to support an existing report or submit anyway.
        </div>
    </section>

    {{-- 3. Discovering problems --}}
    <section class="ug-section anim-1" id="ug-discovering">
        <div class="ug-head">
            <span class="ug-num">3</span>
            <h2 class="ug-title">Discovering problems</h2>
        </div>
        <p class="ug-lede">Finding the problems already reported, so you build on evidence instead of duplicating work.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="search" aria-hidden="true"></i>Browsing and searching</div>
                <p>Browse all approved problems, or search by keyword. Narrowing to a single category is useful when you already know the area you want to work in.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="folder-open" aria-hidden="true"></i>Viewing a problem</div>
                <p>Opening a problem shows its description, how often it occurs, who it affects, its impact, and — where available — any supporting evidence that was submitted with it.</p>
            </div>
        </div>
    </section>

    {{-- 4. Capstone opportunities --}}
    <section class="ug-section anim-1" id="ug-opportunities">
        <div class="ug-head">
            <span class="ug-num">4</span>
            <h2 class="ug-title">Understanding capstone opportunities</h2>
        </div>
        <p class="ug-lede">Two different things can appear on the Capstone Opportunities page. They are not interchangeable.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="building-2" aria-hidden="true"></i>Office-Backed Opportunity</div>
                <p>An approved institutional problem that an office marked as suitable capstone material, together with the office that stands behind it. The office is named, and its representative is the person you deal with.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="sparkles" aria-hidden="true"></i>Community-Generated Idea</div>
                <p>A project concept the DSS generated from community-reported problems, with no office attached. It is an idea to develop, not an opportunity an office has offered.</p>
            </div>
        </div>

        <div class="ug-flow" style="margin-top:14px">
            <span class="ug-step"><i data-lucide="message-square" aria-hidden="true"></i>Community reports</span>
            <span class="ug-arrow">→</span>
            <span class="ug-step"><i data-lucide="cpu" aria-hidden="true"></i>DSS evaluation</span>
            <span class="ug-arrow">→</span>
            <span class="ug-step"><i data-lucide="lightbulb" aria-hidden="true"></i>Generated ideas</span>
        </div>

        <div class="ug-note">
            Both come from the same DSS workflow and the same institutional evidence. The difference is
            whether an office is attached to the underlying problem.
        </div>
    </section>

    {{-- 5. DSS analysis --}}
    <section class="ug-section anim-1" id="ug-analysis">
        <div class="ug-head">
            <span class="ug-num">5</span>
            <h2 class="ug-title">Understanding DSS analysis</h2>
        </div>
        <p class="ug-lede">The signals LIKHA shows, and what each one means.</p>

        <dl class="ug-defs">
            <div class="ug-def">
                <dt>Reports</dt>
                <dd>How many separate reports describe the same underlying problem.</dd>
            </div>
            <div class="ug-def">
                <dt>Votes</dt>
                <dd>How many people marked the problem as one they also experience.</dd>
            </div>
            <div class="ug-def">
                <dt>Frequency</dt>
                <dd>How often the problem occurs, from rare to everyday.</dd>
            </div>
            <div class="ug-def">
                <dt>Impact</dt>
                <dd>How much the problem affects the people caught by it.</dd>
            </div>
            <div class="ug-def">
                <dt>Affected group</dt>
                <dd>Who it affects most, such as students, faculty, staff or administration.</dd>
            </div>
            <div class="ug-def">
                <dt>Severity</dt>
                <dd>How significant or urgent the problem is considered.</dd>
            </div>
            <div class="ug-def">
                <dt>Confidence</dt>
                <dd>How reliable the supporting evidence behind the problem is.</dd>
            </div>
            <div class="ug-def">
                <dt>Evaluation</dt>
                <dd>An overall assessment score combining the signals above.</dd>
            </div>
            <div class="ug-def">
                <dt>Recommendation</dt>
                <dd>The system's overall judgement of the generated idea based on that assessment.</dd>
            </div>
        </dl>
    </section>

    {{-- 6. Saved ideas --}}
    <section class="ug-section anim-1" id="ug-saved">
        <div class="ug-head">
            <span class="ug-num">6</span>
            <h2 class="ug-title">Saved ideas</h2>
        </div>
        <p class="ug-lede">A personal bookmark list, and only that.</p>

        <div class="ug-note ug-note-caution">
            <strong>Saving an idea does not reserve it.</strong> Another group may still be able to work on it.<br>
            <strong>Saving an idea is not office confirmation.</strong> It has no effect on whether an office
            has agreed to anything.<br>
            <strong>Saving an idea does not establish ownership.</strong> It records your interest, not a claim.
        </div>
    </section>

    {{-- 7. Office confirmation --}}
    <section class="ug-section anim-1" id="ug-confirmation">
        <div class="ug-head">
            <span class="ug-num">7</span>
            <h2 class="ug-title">Office confirmation</h2>
        </div>
        <p class="ug-lede">The step that turns an opportunity into something an office stands behind.</p>

        <div class="ug-flow">
            <span class="ug-step"><i data-lucide="compass" aria-hidden="true"></i>Explore</span>
            <span class="ug-arrow">→</span>
            <span class="ug-step"><i data-lucide="message-square" aria-hidden="true"></i>Consult</span>
            <span class="ug-arrow">→</span>
            <span class="ug-step"><i data-lucide="send" aria-hidden="true"></i>Request confirmation</span>
            <span class="ug-arrow">→</span>
            <span class="ug-step"><i data-lucide="user-round" aria-hidden="true"></i>Office representative decides</span>
        </div>

        <dl class="ug-defs" style="margin-top:16px">
            <div class="ug-def">
                <dt>Available</dt>
                <dd>The opportunity can still be requested. No group holds it yet.</dd>
            </div>
            <div class="ug-def">
                <dt>Confirmation requested</dt>
                <dd>Your request has been sent and is waiting. The opportunity is still available to others while it waits.</dd>
            </div>
            <div class="ug-def">
                <dt>Office-confirmed</dt>
                <dd>The office has confirmed. You can see this as the requesting group.</dd>
            </div>
            <div class="ug-def">
                <dt>Taken</dt>
                <dd>The opportunity is held and no longer available to claim.</dd>
            </div>
            <div class="ug-def">
                <dt>Declined</dt>
                <dd>The office did not confirm. The opportunity returns to available so another group can try.</dd>
            </div>
        </dl>

        <div class="ug-note ug-note-caution">
            <strong>Consultation happens outside LIKHA.</strong> You are expected to consult the office
            representative yourself before requesting confirmation. LIKHA does not record a consultation,
            does not verify that one happened, and a request does not reserve the opportunity.
        </div>
    </section>

    {{-- 8. Office representatives --}}
    <section class="ug-section anim-1" id="ug-representatives">
        <div class="ug-head">
            <span class="ug-num">8</span>
            <h2 class="ug-title">Office representatives</h2>
        </div>
        <p class="ug-lede">The person an office nominates to speak for it.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="inbox" aria-hidden="true"></i>Receiving requests</div>
                <p>When a group requests confirmation, the office's representative is notified through the notification bell and the request appears in their confirmation queue.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="file-text" aria-hidden="true"></i>Reviewing a request</div>
                <p>The queue shows the opportunity, the requesting group leader, the office, when the request was made, the source problem evidence, and the consultation guidance.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="check" aria-hidden="true"></i>Confirming or declining</div>
                <p>Confirming marks the request confirmed and the opportunity as taken for that group. Declining returns the opportunity to available for another group.</p>
            </div>
        </div>

        <div class="ug-note">
            Representation is a relationship an office holds with a person, not a system role. It can
            change, and only the office's current representative can confirm or decline its requests.
        </div>
    </section>

    {{-- 9. Reviewers --}}
    <section class="ug-section anim-1" id="ug-reviewers">
        <div class="ug-head">
            <span class="ug-num">9</span>
            <h2 class="ug-title">Reviewers</h2>
        </div>
        <p class="ug-lede">Who checks office institutional reports before they reach the DSS.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="clipboard-check" aria-hidden="true"></i>Reviewing office reports</div>
                <p>Office institutional reports stay pending until a reviewer looks at them. Reviewers see the reports for the categories they are assigned.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="check" aria-hidden="true"></i>Approving</div>
                <p>An approved report enters the DSS pipeline, so it can be evaluated and can produce an office-backed opportunity.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="x" aria-hidden="true"></i>Rejecting</div>
                <p>A rejected report does not enter the pipeline and does not produce a DSS opportunity.</p>
            </div>
        </div>

        <div class="ug-note">
            Reviewing an office report is a separate responsibility from representing an office.
            A reviewer is not automatically an office representative, and an office representative is not automatically a reviewer.
        </div>
    </section>

    {{-- 10. Notifications --}}
    <section class="ug-section anim-1" id="ug-notifications">
        <div class="ug-head">
            <span class="ug-num">10</span>
            <h2 class="ug-title">Notifications</h2>
        </div>
        <p class="ug-lede">How LIKHA tells you that something needs your attention.</p>

        <div class="ug-items">
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="bell" aria-hidden="true"></i>The notification bell</div>
                <p>The bell sits at the top right of the content area. A badge on it means you have unread notifications.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="mouse-pointer-click" aria-hidden="true"></i>Opening a notification</div>
                <p>Opening the bell lists your notifications. Selecting one marks it read and takes you to the relevant workflow — a generated idea, a review queue, or a confirmation queue.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="clipboard-check" aria-hidden="true"></i>Office report reviews</div>
                <p>A reviewer is notified when a new office report is waiting for review.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="send" aria-hidden="true"></i>Confirmation requests</div>
                <p>The office's current representative is notified when a group requests confirmation, and can go straight to the request from there.</p>
            </div>
            <div class="ug-item">
                <div class="ug-item-head"><i data-lucide="lightbulb" aria-hidden="true"></i>Generated ideas</div>
                <p>Contributors to a problem are notified when the DSS generates an idea from problems they reported or supported.</p>
            </div>
        </div>
    </section>

    {{-- 11. Profile and account --}}
    <section class="ug-section anim-1" id="ug-account">
        <div class="ug-head">
            <span class="ug-num">11</span>
            <h2 class="ug-title">Profile and account</h2>
        </div>
        <p class="ug-lede">Everything you can change about your own account.</p>

        <div class="ug-defs">
            <div class="ug-def">
                <dt>Profile information</dt>
                <dd>Your name and email address.</dd>
            </div>
            <div class="ug-def">
                <dt>Change password</dt>
                <dd>Update your password using your current password.</dd>
            </div>
            <div class="ug-def">
                <dt>Saved ideas</dt>
                <dd>Your personal bookmarks. See section 6.</dd>
            </div>
            <div class="ug-def">
                <dt>My contribution</dt>
                <dd>A summary of your reports, supports, evidence and generated ideas.</dd>
            </div>
            <div class="ug-def">
                <dt>Confirmation requests</dt>
                <dd>Shown to you if you currently represent an active office. See section 8.</dd>
            </div>
            <div class="ug-def">
                <dt>Light / dark mode</dt>
                <dd>Switch the appearance. Your choice is remembered.</dd>
            </div>
            <div class="ug-def">
                <dt>Logout</dt>
                <dd>Sign out of LIKHA on this device.</dd>
            </div>
        </div>
    </section>

    {{-- 12. The LIKHA principle --}}
    <section class="ug-principle anim-1" id="ug-principle">
        <p class="ug-principle-line ug-principle-dss">THE DSS DECIDES.</p>
        <p class="ug-principle-line ug-principle-ai">THE AI EXPLAINS.</p>
        <p class="ug-principle-sub">
            LIKHA's recommendations and opportunities are produced by rule-based DSS processing working from
            institutional evidence: reports, votes, frequency, impact, severity, confidence and the
            configured thresholds. That processing decides what exists and what qualifies.
            <br><br>
            The AI layer is an enhancement only. It improves how a title, description or objective is worded and presented.
            It does not decide qualification, severity, confidence, thresholds, office scope, or whether an opportunity exists.
        </p>
    </section>

</div>
@endsection