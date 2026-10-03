@extends('layouts.app')

@section('title', 'Terms & Conditions')
@section('subtitle', 'Rules and responsibilities for using LIKHA')

@section('content')
@include('layouts.partials.design-system')

{{-- Terms & Conditions: documents the rules and behavior actually implemented.
     Styles are scoped to this page so the global shell stays untouched. --}}
<style>
    .tc-shell { display: flex; flex-direction: column; gap: 16px; }

    .tc-masthead {
        border: 1px solid var(--border);
        border-radius: 16px;
        background: var(--surface);
        padding: 26px 24px;
    }
    .tc-masthead-rule {
        height: 3px; width: 64px; border-radius: 999px;
        background: linear-gradient(to right, var(--amber), #f97316);
        margin-bottom: 18px;
    }
    .tc-masthead-kicker {
        font-size: 10.5px; font-weight: 700; letter-spacing: .22em;
        text-transform: uppercase; color: var(--amber); margin: 0 0 8px;
    }
    .tc-masthead-title {
        font-family: 'Sora', sans-serif;
        font-size: clamp(21px, 3.4vw, 29px); font-weight: 800;
        line-height: 1.2; color: var(--text); margin: 0 0 10px;
    }
    .tc-masthead-sub {
        font-size: 14px; color: var(--text2); line-height: 1.7;
        max-width: 720px; margin: 0 0 12px;
    }
    .tc-masthead-meta {
        font-size: 11.5px; color: var(--text3); letter-spacing: .04em;
        text-transform: uppercase; font-weight: 600; margin: 0 0 16px;
    }
    .tc-masthead-note {
        margin: 0; padding-top: 14px;
        border-top: 1px solid var(--border);
        font-size: 12px; line-height: 1.7; color: var(--text3);
        max-width: 760px;
    }

    .tc-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }

    .tc-glance {
        border: 1px solid var(--amber-mid); border-radius: 14px;
        background: var(--amber-dim); padding: 18px 20px;
    }
    .tc-glance-title {
        font-size: 10.5px; font-weight: 700; letter-spacing: .2em;
        text-transform: uppercase; color: var(--amber); margin: 0 0 12px;
    }
    .tc-glance-list { margin: 0; padding: 0; list-style: none; }
    .tc-glance-list li {
        display: flex; align-items: flex-start; gap: 9px;
        padding: 4px 0; font-size: 12.5px; line-height: 1.65; color: var(--text2);
    }
    .tc-glance-list i[data-lucide] {
        width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; color: var(--amber);
    }

    .tc-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        scroll-margin-top: 20px;
    }
    .tc-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .tc-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; flex-shrink: 0; border-radius: 9px;
        background: var(--amber-dim); border: 1px solid var(--amber-mid);
        color: var(--amber); font-size: 12px; font-weight: 700;
    }
    .tc-title {
        font-family: 'Sora', sans-serif; font-size: 15.5px; font-weight: 700;
        color: var(--text); margin: 0;
    }

    .tc-sub {
        font-size: 12px; font-weight: 700; letter-spacing: .1em;
        text-transform: uppercase; color: var(--text3); margin: 16px 0 8px;
    }
    .tc-sub:first-of-type { margin-top: 4px; }

    .tc-text { margin: 0 0 10px; font-size: 12.5px; line-height: 1.75; color: var(--text2); }
    .tc-text strong { color: var(--text); font-weight: 600; }

    .tc-list { margin: 0; padding: 0; list-style: none; }
    .tc-list li {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 3px 0; font-size: 12.5px; line-height: 1.7; color: var(--text2);
    }
    .tc-list li::before {
        content: ''; flex-shrink: 0; margin-top: 9px;
        width: 4px; height: 4px; border-radius: 50%; background: var(--amber-mid);
    }

    .tc-not {
        display: flex; align-items: flex-start; gap: 8px;
        margin: 4px 0 0; padding: 4px 0 4px 10px;
        font-size: 12.5px; line-height: 1.7; color: var(--text2);
        border-left: 2px solid var(--border);
    }
    .tc-not li { list-style: none; padding: 0; }
    .tc-not { display: block; }
    .tc-not ul { margin: 0; padding: 0; }
    .tc-not li {
        position: relative; padding: 3px 0 3px 16px; list-style: none;
    }
    .tc-not li::before {
        content: ''; position: absolute; left: 0; top: 11px;
        width: 5px; height: 5px; border-radius: 50%; background: var(--red);
    }

    .tc-emph {
        border: 1px solid var(--amber-mid); border-radius: 12px;
        background: var(--amber-dim); padding: 16px 18px;
    }
    .tc-emph-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 700; color: var(--amber);
        margin: 0 0 8px;
    }
    .tc-emph-title i[data-lucide] { width: 16px; height: 16px; flex-shrink: 0; }
    .tc-emph p { margin: 0 0 8px; font-size: 12.5px; line-height: 1.75; color: var(--text2); }
    .tc-emph p:last-child { margin-bottom: 0; }

    .tc-limit {
        border: 1px solid var(--border); border-radius: 12px;
        background: var(--surface2); padding: 16px 18px;
    }
    .tc-limit-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 700; color: var(--text); margin: 0 0 8px;
    }
    .tc-limit-title i[data-lucide] { width: 16px; height: 16px; flex-shrink: 0; color: var(--text3); }
    .tc-limit p { margin: 0 0 8px; font-size: 12.5px; line-height: 1.75; color: var(--text2); }
    .tc-limit p:last-child { margin-bottom: 0; }

    .tc-callout {
        border-left: 3px solid var(--amber); border-radius: 0 12px 12px 0;
        background: var(--surface2); padding: 12px 16px;
        font-size: 12.5px; line-height: 1.75; color: var(--text2);
    }

    .tc-principle {
        border: 1px solid var(--amber-mid); border-radius: 14px;
        background: var(--surface); padding: 22px 24px; text-align: center;
    }
    .tc-principle-line {
        font-family: 'Sora', sans-serif; font-size: clamp(17px, 3vw, 24px);
        font-weight: 800; letter-spacing: .02em; line-height: 1.3; margin: 0;
    }
    .tc-principle-dss { color: var(--text); }
    .tc-principle-ai { color: var(--amber); }
    .tc-principle-sub {
        margin: 12px auto 0; max-width: 660px;
        font-size: 12.5px; line-height: 1.75; color: var(--text2);
    }

    .tc-pending {
        border: 1px dashed var(--border); border-radius: 12px;
        background: var(--surface2); padding: 18px 20px;
    }
    .tc-pending-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 700; color: var(--text); margin: 0 0 10px;
    }
    .tc-pending-title i[data-lucide] { width: 16px; height: 16px; flex-shrink: 0; color: var(--text3); }

    .tc-closing {
        border: 1px solid var(--border); border-radius: 14px;
        background: var(--surface); padding: 22px 24px;
    }
    .tc-closing-title {
        font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
        color: var(--text); margin: 0 0 10px;
    }
    .tc-closing p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text2); }
</style>

<div class="tc-shell mx-auto max-w-4xl">

    {{-- Masthead --}}
    <header class="tc-masthead anim-1">
        <div class="tc-masthead-rule"></div>
        <p class="tc-masthead-kicker">Terms &amp; Conditions</p>
        <h1 class="tc-masthead-title">Rules and responsibilities for using LIKHA</h1>
        <p class="tc-masthead-sub">
            These Terms describe the current rules, responsibilities, and system behavior associated with
            using LIKHA.
        </p>
        <p class="tc-masthead-meta">Current system documentation</p>

        <div class="tc-links">
            <a href="{{ route('help.privacy') }}" class="btn-ghost text-sm">
                <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                Privacy Rights
            </a>
            @auth
                <a href="{{ route('help.user-guide') }}" class="btn-ghost text-sm">
                    <i data-lucide="book-open" class="h-4 w-4" aria-hidden="true"></i>
                    User Guide
                </a>
            @endauth
        </div>

        <p class="tc-masthead-note">
            Where a formal institutional policy has not yet been configured, this page does not create one.
            Such items are identified separately.
        </p>
    </header>

    {{-- At a glance --}}
    <section class="tc-glance anim-1" aria-label="Summary">
        <p class="tc-glance-title">Before you use LIKHA</p>
        <ul class="tc-glance-list">
            <li><i data-lucide="check" aria-hidden="true"></i>An authenticated and verified account is required for the application features.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Institutional problem submissions are expected to contain meaningful, specific information.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Each user can support a problem once, and can remove that support later.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Saved Ideas are personal bookmarks, not ownership or reservations.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Office confirmation is not final capstone approval.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>DSS outputs support exploration and consultation.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>AI enhancement does not decide qualification or scores.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Some features depend on external services.</li>
        </ul>
    </section>

    {{-- 01 About using LIKHA --}}
    <section class="tc-section anim-1" id="tc-about">
        <div class="tc-head">
            <span class="tc-num">01</span>
            <h2 class="tc-title">About using LIKHA</h2>
        </div>
        <p class="tc-text">
            LIKHA is a web-based decision support system that connects institutional problems with capstone opportunities.
        </p>
        <p class="tc-text">LIKHA provides:</p>
        <ul class="tc-list">
            <li>Institutional problem submission</li>
            <li>Evidence-based problem analysis</li>
            <li>Discovery of institutional problems</li>
            <li>Capstone opportunity exploration</li>
            <li>Office-backed opportunities</li>
            <li>Community-generated ideas</li>
            <li>Office confirmation workflows</li>
            <li>Adviser-related evaluation features</li>
        </ul>

        <div class="tc-emph" style="margin-top:16px">
            <p class="tc-emph-title"><i data-lucide="cpu" aria-hidden="true"></i>How outputs are produced</p>
            <p>
                The DSS performs the underlying rule-based analysis and determines the resulting recommendation.
                AI enhancement is used only to improve wording and presentation.
            </p>
            <p>
                LIKHA is not an AI capstone generator. Capstone concepts come from the rule-based analysis of
                submitted institutional evidence.
            </p>
        </div>
    </section>

    {{-- 02 Your account --}}
    <section class="tc-section anim-1" id="tc-account">
        <div class="tc-head">
            <span class="tc-num">02</span>
            <h2 class="tc-title">Your account</h2>
        </div>

        <p class="tc-sub">Account creation</p>
        <p class="tc-text">
            Accounts may be created through normal registration or through Google sign-in. Email verification is
            required before accessing authenticated application features.
        </p>

        <p class="tc-sub">Passwords</p>
        <p class="tc-text">
            Passwords are stored using password hashing rather than as readable passwords.
        </p>

        <p class="tc-sub">Account responsibilities</p>
        <ul class="tc-list">
            <li>Keep your account credentials confidential.</li>
            <li>Use your own account when contributing.</li>
            <li>Provide the information the system requires.</li>
            <li>Do not attempt to access features outside your authorization.</li>
        </ul>

        <p class="tc-sub">Account deletion</p>
        <p class="tc-text">
            You can request account deletion through the available profile functionality.
            Account deletion may be unavailable while certain office-representative or office-confirmation records still reference the account.
        </p>
        <div class="tc-callout">
            For detailed data-handling behavior, see the
            <a href="{{ route('help.privacy') }}" style="color:var(--amber);font-weight:600;text-decoration:none">Privacy Rights</a> page.
        </div>
    </section>

    {{-- 03 Submitting institutional problems --}}
    <section class="tc-section anim-1" id="tc-submitting">
        <div class="tc-head">
            <span class="tc-num">03</span>
            <h2 class="tc-title">Submitting institutional problems</h2>
        </div>
        <p class="tc-text">Authenticated users can submit institutional problems.</p>
        <p class="tc-text">LIKHA expects the following information, in your own words:</p>
        <ul class="tc-list">
            <li>Problem title</li>
            <li>Category</li>
            <li>Description</li>
            <li>Impact</li>
            <li>Frequency</li>
            <li>Current process</li>
            <li>Affected users and affected groups</li>
        </ul>

        <p class="tc-sub">Mechanical validation</p>
        <p class="tc-text">LIKHA applies mechanical validation to submissions. For example:</p>
        <ul class="tc-list">
            <li>Minimum text lengths</li>
            <li>A meaningful-description check</li>
            <li>A placeholder-text check</li>
            <li>A repeated-character check</li>
        </ul>
        <div class="tc-callout">
            These are mechanical quality checks. LIKHA does not currently enforce a formal truthfulness policy,
            and does not determine whether a statement is true.
        </div>

        <p class="tc-sub">Submission lifecycle</p>
        <p class="tc-text">
            A submission starts as <strong>Pending</strong> and becomes <strong>Approved</strong> or
            <strong>Rejected</strong>. Approved submissions may become visible and contribute to DSS analysis.
            Rejected submissions remain as records and are not presented as approved opportunities.
        </p>

        <p class="tc-sub">Editing</p>
        <div class="tc-emph">
            <p class="tc-emph-title"><i data-lucide="pencil-off" aria-hidden="true"></i>No editing after submission</p>
            <p>
                LIKHA currently does not provide a user-facing edit, withdraw, or delete function for submitted
                institutional problems after submission.
            </p>
        </div>
    </section>

    {{-- 04 Anonymous submissions --}}
    <section class="tc-section anim-1" id="tc-anonymous">
        <div class="tc-head">
            <span class="tc-num">04</span>
            <h2 class="tc-title">Anonymous submissions</h2>
        </div>
        <div class="tc-emph">
            <p class="tc-emph-title"><i data-lucide="eye-off" aria-hidden="true"></i>What anonymous submission does</p>
            <p>
                When you select <strong>Submit anonymously</strong>, LIKHA removes the user association from that
                feedback record. The interface displays the contributor as anonymous.
            </p>
            <p>
                Information included in the submission itself or in uploaded evidence may still identify a person.
            </p>
        </div>
        <div class="tc-callout">
            For detailed information about data handling, see the
            <a href="{{ route('help.privacy') }}" style="color:var(--amber);font-weight:600;text-decoration:none">Privacy Rights</a> page.
        </div>
    </section>

    {{-- 05 Uploaded evidence --}}
    <section class="tc-section anim-1" id="tc-evidence">
        <div class="tc-head">
            <span class="tc-num">05</span>
            <h2 class="tc-title">Uploaded evidence</h2>
        </div>
        <p class="tc-sub">Attachment</p>
        <ul class="tc-list">
            <li>One file</li>
            <li>JPG, JPEG, PNG or PDF</li>
            <li>Maximum 5 MB</li>
        </ul>

        <p class="tc-sub">Evidence</p>
        <ul class="tc-list">
            <li>Up to 5 files</li>
            <li>JPG, JPEG, WEBP or PDF</li>
            <li>Maximum 10 MB per file</li>
            <li>Optional captions</li>
        </ul>

        <p class="tc-text">
            LIKHA validates uploaded files and rejects uploads outside these formats and limits.
        </p>

        <div class="tc-limit" style="margin-top:14px">
            <p class="tc-limit-title"><i data-lucide="lightbulb" aria-hidden="true"></i>When uploading evidence</p>
            <p>Only upload evidence that is relevant to the institutional problem.</p>
            <p>Avoid including unnecessary personal or sensitive information in uploaded evidence.</p>
        </div>
        <div class="tc-callout">
            For actual storage and deletion behavior, see the
            <a href="{{ route('help.privacy') }}" style="color:var(--amber);font-weight:600;text-decoration:none">Privacy Rights</a> page.
        </div>
    </section>

    {{-- 06 Duplicate and rapid submissions --}}
    <section class="tc-section anim-1" id="tc-duplicates">
        <div class="tc-head">
            <span class="tc-num">06</span>
            <h2 class="tc-title">Duplicate and rapid submissions</h2>
        </div>

        <p class="tc-sub">Before submission</p>
        <p class="tc-text">
            LIKHA can show similar approved problems before you submit. You may still choose to submit anyway.
        </p>

        <p class="tc-sub">After submission</p>
        <p class="tc-text">
            Highly similar submissions can be flagged for review, and rapid submissions can also be flagged.
        </p>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="flag" aria-hidden="true"></i>What flagging means</p>
            <p>
                Flagging does not mean the submission is automatically deleted. Flagged content is withheld from
                normal visibility and DSS processing until it can be reviewed.
            </p>
            <p>
                Flagging is a mechanical similarity and rate check. It is not a judgment about your intent.
            </p>
        </div>
    </section>

    {{-- 07 Voting, comments and contributions --}}
    <section class="tc-section anim-1" id="tc-contributions">
        <div class="tc-head">
            <span class="tc-num">07</span>
            <h2 class="tc-title">Voting, comments and contributions</h2>
        </div>

        <p class="tc-sub">Voting</p>
        <ul class="tc-list">
            <li>Only approved and non-flagged problems can receive support.</li>
            <li>Each user can support a problem once.</li>
            <li>Supporting again toggles the support off.</li>
            <li>Guests must sign in to support a problem.</li>
        </ul>
        <div class="tc-callout">
            The one-support-per-user rule is the only voting restriction LIKHA implements. LIKHA does not detect
            coordinated voting.
        </div>

        <p class="tc-sub">Comments</p>
        <ul class="tc-list">
            <li>Comments can be added to approved problems.</li>
            <li>Comments are currently append-only from your side.</li>
            <li>There is no user-facing comment edit or delete function.</li>
        </ul>
    </section>

    {{-- 08 Capstone opportunities --}}
    <section class="tc-section anim-1" id="tc-opportunities">
        <div class="tc-head">
            <span class="tc-num">08</span>
            <h2 class="tc-title">Capstone opportunities</h2>
        </div>

        <p class="tc-sub">Office-backed opportunities</p>
        <p class="tc-text">
            These originate from approved institutional problems associated with an office and marked as
            capstone-worthy.
        </p>

        <p class="tc-sub">Community-generated ideas</p>
        <p class="tc-text">
            These are DSS-generated outputs without an attached office.
        </p>

        <p class="tc-sub">Assessments are not guarantees</p>
        <p class="tc-text">
            You may see Severity, Confidence, Evaluation and Recommendation on an opportunity or idea. These are
            assessments used for decision support, not guarantees of any outcome.
        </p>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="triangle-alert" aria-hidden="true"></i>Display is not approval</p>
            <p>
                An opportunity being displayed by LIKHA does not mean that the institution has finally approved
                it as a capstone project.
            </p>
        </div>
    </section>

    {{-- 09 Saved ideas and ownership --}}
    <section class="tc-section anim-1" id="tc-ownership">
        <div class="tc-head">
            <span class="tc-num">09</span>
            <h2 class="tc-title">Saved Ideas and ownership</h2>
        </div>

        <p class="tc-text">Saving an idea does <strong>not</strong>:</p>
        <div class="tc-not">
            <ul>
                <li>reserve the opportunity</li>
                <li>create ownership</li>
                <li>create priority</li>
                <li>prevent another student or group from exploring the same idea</li>
            </ul>
        </div>

        <p class="tc-sub">What a Saved Idea is</p>
        <p class="tc-text">
            A Saved Idea is a personal bookmark that lets you keep track of an idea and its progress. The
            available personal statuses are:
        </p>
        <ul class="tc-list">
            <li>Exploring</li>
            <li>Adopted</li>
            <li>In Progress</li>
            <li>Completed</li>
        </ul>
        <p class="tc-text">
            These are personal tracking statuses and are visible and editable only by you.
        </p>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="user-round-x" aria-hidden="true"></i>Ownership</p>
            <p>Saved Ideas do not determine who owns a capstone project.</p>
        </div>
    </section>

    {{-- 10 Office confirmation --}}
    <section class="tc-section anim-1" id="tc-confirmation">
        <div class="tc-head">
            <span class="tc-num">10</span>
            <h2 class="tc-title">Office confirmation</h2>
        </div>

        <p class="tc-sub">Request</p>
        <p class="tc-text">
            A student or group representative can request confirmation for an office-backed opportunity.
        </p>
        <div class="tc-not">
            <ul>
                <li>A confirmation request does not reserve the opportunity.</li>
                <li>Submitting a request does not create ownership.</li>
            </ul>
        </div>
        <p class="tc-text">
            Multiple pending requests may exist before any of them is confirmed.
        </p>

        <p class="tc-sub">Consultation</p>
        <div class="tc-callout">
            Consultation with the office representative occurs outside LIKHA and is not recorded as an
            in-system consultation.
        </div>

        <p class="tc-sub">Confirm</p>
        <p class="tc-text">When the current representative confirms:</p>
        <ul class="tc-list">
            <li>The request becomes confirmed.</li>
            <li>The opportunity becomes taken for the requesting group.</li>
            <li>Competing pending requests become stale.</li>
        </ul>

        <p class="tc-sub">Decline</p>
        <p class="tc-text">When the current representative declines:</p>
        <ul class="tc-list">
            <li>The request becomes declined.</li>
            <li>The opportunity becomes available again.</li>
        </ul>

        <p class="tc-sub">Authority</p>
        <p class="tc-text">Only the current office representative may Confirm or Decline.</p>
        <div class="tc-callout">
            Office reviewer status does not automatically grant office representative authority.
        </div>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="triangle-alert" aria-hidden="true"></i>Confirmation is not final approval</p>
            <p>Office confirmation is not final institutional approval of a capstone project.</p>
        </div>
    </section>

    {{-- 11 Office representatives and reviewers --}}
    <section class="tc-section anim-1" id="tc-roles">
        <div class="tc-head">
            <span class="tc-num">11</span>
            <h2 class="tc-title">Office representatives and reviewers</h2>
        </div>

        <p class="tc-sub">Office Representative</p>
        <p class="tc-text">
            Representative status comes from the office record and an active representation assignment. It is not
            a separate system role. A user may represent more than one office.
        </p>

        <p class="tc-sub">Reviewer</p>
        <p class="tc-text">
            Reviewers are users with the applicable reviewer role and a category assignment for the categories
            they review.
        </p>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="split" aria-hidden="true"></i>Two separate responsibilities</p>
            <p>Being a reviewer does not automatically make someone an office representative.</p>
            <p>Being an office representative does not automatically make someone a reviewer.</p>
        </div>

        <p class="tc-sub">Reviewer behavior</p>
        <ul class="tc-list">
            <li>Reviewers review office reports assigned to their categories.</li>
            <li>Self-review is blocked while another eligible reviewer exists.</li>
        </ul>

        <p class="tc-sub">Administrator</p>
        <p class="tc-text">
            Administrators manage system configuration and moderation functions, including feedback moderation,
            user and role management, offices, category assignments, priority problems, evidence, and system
            settings.
        </p>
    </section>

    {{-- 12 DSS and AI --}}
    <section class="tc-section anim-1" id="tc-dss-ai">
        <div class="tc-head">
            <span class="tc-num">12</span>
            <h2 class="tc-title">DSS and AI</h2>
        </div>

        <div class="tc-principle" style="margin-bottom:16px">
            <p class="tc-principle-line tc-principle-dss">THE DSS DECIDES.</p>
            <p class="tc-principle-line tc-principle-ai">THE AI EXPLAINS.</p>
            <p class="tc-principle-sub">
                The DSS performs rule-based processing. AI enhancement is an optional, presentation-oriented
                layer.
            </p>
        </div>

        <p class="tc-sub">The DSS</p>
        <p class="tc-text">The DSS performs rule-based processing involving areas such as:</p>
        <ul class="tc-list">
            <li>Clustering</li>
            <li>Severity</li>
            <li>Confidence</li>
            <li>Evaluation</li>
            <li>Qualification thresholds</li>
            <li>Opportunity generation</li>
        </ul>

        <p class="tc-sub">AI enhancement</p>
        <p class="tc-text">
            AI enhancement is optional and presentation-oriented. It can improve wording for the title,
            description, general objective and specific objectives.
        </p>

        <div class="tc-emph" style="margin-top:14px">
            <p class="tc-emph-title"><i data-lucide="shield" aria-hidden="true"></i>What AI enhancement does not do</p>
            <p>AI enhancement does not determine whether an opportunity qualifies.</p>
            <p>AI enhancement does not change severity.</p>
            <p>AI enhancement does not change confidence.</p>
            <p>AI enhancement does not change evaluation.</p>
            <p>AI enhancement does not change thresholds.</p>
            <p>AI enhancement does not change office scope.</p>
            <p>If AI enhancement is unavailable or fails, the original wording is retained.</p>
            <p>AI enhancement is disabled by default in the inspected configuration.</p>
        </div>
    </section>

    {{-- 13 Moderation and system safeguards --}}
    <section class="tc-section anim-1" id="tc-safeguards">
        <div class="tc-head">
            <span class="tc-num">13</span>
            <h2 class="tc-title">Moderation and system safeguards</h2>
        </div>
        <p class="tc-text">LIKHA implements the following mechanical safeguards:</p>
        <ul class="tc-list">
            <li>Placeholder-text rejection</li>
            <li>Repeated-character checks</li>
            <li>Meaningful-description checks</li>
            <li>Duplicate similarity detection</li>
            <li>Rapid-submission detection</li>
            <li>Submission throttling</li>
            <li>Login rate limiting</li>
            <li>Administrative moderation</li>
            <li>Reviewer self-review prevention</li>
            <li>Evidence administration controls</li>
        </ul>
        <div class="tc-callout">
            These safeguards are designed to support the integrity and normal operation of the system. Not every
            type of misuse is automatically detected.
        </div>
    </section>

    {{-- 14 System limitations --}}
    <section class="tc-section anim-1" id="tc-limitations">
        <div class="tc-head">
            <span class="tc-num">14</span>
            <h2 class="tc-title">System limitations</h2>
        </div>
        <ul class="tc-list">
            <li>Some features depend on external services.</li>
            <li>Google sign-in depends on Google availability.</li>
            <li>Email verification and password reset depend on email delivery.</li>
            <li>AI enhancement may be unavailable or fail; the original wording is then retained.</li>
            <li>Notifications use queued, database-backed processing and may not appear instantly.</li>
            <li>LIKHA does not provide an uptime or response-time guarantee.</li>
            <li>DSS outputs are decision-support assessments and are not guaranteed outcomes.</li>
        </ul>
    </section>

    {{-- 15 Privacy and data handling --}}
    <section class="tc-section anim-1" id="tc-privacy">
        <div class="tc-head">
            <span class="tc-num">15</span>
            <h2 class="tc-title">Privacy and data handling</h2>
        </div>
        <p class="tc-text">
            LIKHA's handling of personal information, uploaded evidence, account deletion, retention, external
            services, and available privacy controls is described separately in the Privacy Rights page.
        </p>
        <div class="tc-links">
            <a href="{{ route('help.privacy') }}" class="btn-amber text-sm">
                <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                View Privacy Rights
            </a>
        </div>
    </section>

    {{-- Policies requiring institutional definition --}}
    <section class="tc-pending anim-1" id="tc-institutional">
        <p class="tc-pending-title"><i data-lucide="landmark" aria-hidden="true"></i>Policies requiring institutional definition</p>
        <p class="tc-text">
            The current application does not define formal policies for the following matters:
        </p>
        <ul class="tc-list">
            <li>Account suspension and disciplinary procedures</li>
            <li>A formal acceptable-use policy beyond the system safeguards listed above</li>
            <li>Intellectual-property ownership</li>
            <li>Final institutional capstone approval</li>
            <li>A complaint or grievance procedure</li>
            <li>Governing law and dispute resolution</li>
            <li>Institutional liability statements</li>
            <li>Formal office response-time commitments</li>
        </ul>
        <p class="tc-text">
            These matters are not currently defined by the LIKHA application.
            They should be established separately by the responsible institution or project authority if required.
        </p>
    </section>

    {{-- Closing --}}
    <section class="tc-closing anim-1">
        <p class="tc-closing-title">These Terms describe the current LIKHA system.</p>
        <p>
            Where a matter is not defined by the application, it has been identified above as requiring
            institutional definition rather than described as a rule that exists. This page does not establish
            legal obligations, penalties or remedies that LIKHA does not implement.
        </p>
    </section>

</div>
@endsection