@extends('layouts.app')

@section('title', 'Privacy Rights')
@section('subtitle', 'How LIKHA handles your information')

@section('content')
@include('layouts.partials.design-system')

{{-- Privacy Rights: documentation of the information handling actually implemented.
     Styles are scoped to this page so the global shell stays untouched. --}}
<style>
    .pr-shell { display: flex; flex-direction: column; gap: 16px; }

    .pr-masthead {
        border: 1px solid var(--border);
        border-radius: 16px;
        background: var(--surface);
        padding: 26px 24px;
    }
    .pr-masthead-rule {
        height: 3px; width: 64px; border-radius: 999px;
        background: linear-gradient(to right, var(--amber), #f97316);
        margin-bottom: 18px;
    }
    .pr-masthead-kicker {
        font-size: 10.5px; font-weight: 700; letter-spacing: .22em;
        text-transform: uppercase; color: var(--amber); margin: 0 0 8px;
    }
    .pr-masthead-title {
        font-family: 'Sora', sans-serif;
        font-size: clamp(21px, 3.4vw, 29px); font-weight: 800;
        line-height: 1.2; color: var(--text); margin: 0 0 10px;
    }
    .pr-masthead-sub {
        font-size: 14px; color: var(--text2); line-height: 1.7;
        max-width: 720px; margin: 0;
    }
    .pr-masthead-note {
        margin: 18px 0 0; padding-top: 14px;
        border-top: 1px solid var(--border);
        font-size: 12px; line-height: 1.7; color: var(--text3);
        max-width: 760px;
    }

    .pr-glance {
        border: 1px solid var(--amber-mid); border-radius: 14px;
        background: var(--amber-dim); padding: 18px 20px;
    }
    .pr-glance-title {
        font-size: 10.5px; font-weight: 700; letter-spacing: .2em;
        text-transform: uppercase; color: var(--amber); margin: 0 0 12px;
    }
    .pr-glance-list { margin: 0; padding: 0; list-style: none; }
    .pr-glance-list li {
        display: flex; align-items: flex-start; gap: 9px;
        padding: 4px 0; font-size: 12.5px; line-height: 1.65; color: var(--text2);
    }
    .pr-glance-list i[data-lucide] {
        width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; color: var(--amber);
    }

    .pr-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        scroll-margin-top: 20px;
    }
    .pr-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .pr-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; flex-shrink: 0; border-radius: 9px;
        background: var(--amber-dim); border: 1px solid var(--amber-mid);
        color: var(--amber); font-size: 12px; font-weight: 700;
    }
    .pr-title {
        font-family: 'Sora', sans-serif; font-size: 15.5px; font-weight: 700;
        color: var(--text); margin: 0;
    }

    .pr-sub {
        font-size: 12px; font-weight: 700; letter-spacing: .1em;
        text-transform: uppercase; color: var(--text3);
        margin: 16px 0 8px;
    }
    .pr-sub:first-of-type { margin-top: 4px; }

    .pr-text { margin: 0 0 10px; font-size: 12.5px; line-height: 1.75; color: var(--text2); }
    .pr-text strong { color: var(--text); font-weight: 600; }

    .pr-list { margin: 0; padding: 0; list-style: none; }
    .pr-list li {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 3px 0; font-size: 12.5px; line-height: 1.7; color: var(--text2);
    }
    .pr-list li::before {
        content: ''; flex-shrink: 0; margin-top: 9px;
        width: 4px; height: 4px; border-radius: 50%; background: var(--amber-mid);
    }

    /* Access table */
    .pr-access {
        border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
    }
    .pr-access-row {
        display: grid; grid-template-columns: 210px minmax(0, 1fr);
        gap: 4px 16px; padding: 13px 15px;
    }
    .pr-access-row + .pr-access-row { border-top: 1px solid var(--border); }
    @media (max-width: 640px) {
        .pr-access-row { grid-template-columns: minmax(0, 1fr); }
    }
    .pr-access-role {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 600; color: var(--text);
    }
    .pr-access-role i[data-lucide] { width: 15px; height: 15px; flex-shrink: 0; color: var(--amber); }
    .pr-access-detail { font-size: 12.5px; line-height: 1.7; color: var(--text2); }

    /* Emphasis / caution / limit cards */
    .pr-emph {
        border: 1px solid var(--amber-mid); border-radius: 12px;
        background: var(--amber-dim); padding: 16px 18px;
    }
    .pr-emph-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 700; color: var(--amber);
        margin: 0 0 8px;
    }
    .pr-emph-title i[data-lucide] { width: 16px; height: 16px; flex-shrink: 0; }
    .pr-emph p { margin: 0 0 8px; font-size: 12.5px; line-height: 1.75; color: var(--text2); }
    .pr-emph p:last-child { margin-bottom: 0; }

    .pr-limit {
        border: 1px solid var(--border); border-radius: 12px;
        background: var(--surface2); padding: 16px 18px;
    }
    .pr-limit-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 12.5px; font-weight: 700; color: var(--text); margin: 0 0 8px;
    }
    .pr-limit-title i[data-lucide] { width: 16px; height: 16px; flex-shrink: 0; color: var(--text3); }
    .pr-limit p { margin: 0; font-size: 12.5px; line-height: 1.75; color: var(--text2); }

    .pr-callout {
        border-left: 3px solid var(--amber); border-radius: 0 12px 12px 0;
        background: var(--surface2); padding: 12px 16px;
        font-size: 12.5px; line-height: 1.75; color: var(--text2);
    }

    .pr-closing {
        border: 1px solid var(--border); border-radius: 14px;
        background: var(--surface); padding: 22px 24px;
    }
    .pr-closing-title {
        font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
        color: var(--text); margin: 0 0 10px;
    }
    .pr-closing p { margin: 0; font-size: 12.5px; line-height: 1.8; color: var(--text2); }
</style>

<div class="pr-shell mx-auto max-w-4xl">

    {{-- Masthead --}}
    <header class="pr-masthead anim-1">
        <div class="pr-masthead-rule"></div>
        <p class="pr-masthead-kicker">Privacy Rights</p>
        <h1 class="pr-masthead-title">How LIKHA handles your information</h1>
        <p class="pr-masthead-sub">
            LIKHA collects and uses information to support institutional problem reporting,
            evidence-based analysis, capstone opportunity discovery, review, and related system functions.
        </p>
        <p class="pr-masthead-note">
            This page describes the information handling currently implemented in LIKHA.
            Institutional policies or procedures that have not yet been configured are identified as such.
        </p>
    </header>

    {{-- At a glance --}}
    <section class="pr-glance anim-1" aria-label="Privacy overview">
        <p class="pr-glance-title">Your privacy at a glance</p>
        <ul class="pr-glance-list">
            <li><i data-lucide="check" aria-hidden="true"></i>You can submit anonymously — the submission's contributor link to your account is not stored.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>You control your own account information, password, saved ideas, votes and comments.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>Access to LIKHA areas is controlled by roles and by specific relationships, not open to everyone.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>DSS analysis works from the institutional evidence that submissions provide.</li>
            <li><i data-lucide="check" aria-hidden="true"></i>AI enhancement receives only limited idea text, and never your account details.</li>
        </ul>
    </section>

    {{-- 01 Information we collect --}}
    <section class="pr-section anim-1" id="pr-collect">
        <div class="pr-head">
            <span class="pr-num">01</span>
            <h2 class="pr-title">Information we collect</h2>
        </div>
        <p class="pr-text">
            LIKHA stores information in the categories below.
            Passwords are stored using password hashing rather than as readable passwords.
        </p>

        <p class="pr-sub">Account information</p>
        <ul class="pr-list">
            <li>Your name</li>
            <li>Your email address</li>
            <li>A Google account identifier, when you sign in with Google</li>
            <li>Account role and status information, such as whether your account represents an office or is an office head</li>
            <li>Email verification information</li>
        </ul>

        <p class="pr-sub">Institutional problem submissions</p>
        <p class="pr-text">A submission may contain:</p>
        <ul class="pr-list">
            <li>Problem title and description</li>
            <li>Impact of the problem</li>
            <li>Category and department</li>
            <li>How often it occurs and the current process</li>
            <li>Number of affected users and the affected groups</li>
            <li>The office it belongs to, when you submitted on behalf of an office you represent</li>
            <li>Evidence and attachments you uploaded</li>
            <li>Whether you chose to submit anonymously</li>
            <li>Review and processing information, such as status and who reviewed it</li>
        </ul>

        <p class="pr-sub">Activity and contributions</p>
        <p class="pr-text">LIKHA may also store activity associated with you:</p>
        <ul class="pr-list">
            <li>Votes you cast on problems</li>
            <li>Comments you add</li>
            <li>Saved Ideas</li>
            <li>Adviser reviews</li>
            <li>Notifications addressed to you</li>
            <li>Office confirmation requests you submitted, and the decisions made on them</li>
            <li>Review and decision records where you acted in a reviewer or representative capacity</li>
        </ul>
    </section>

    {{-- 02 Anonymous submissions --}}
    <section class="pr-section anim-1" id="pr-anonymous">
        <div class="pr-head">
            <span class="pr-num">02</span>
            <h2 class="pr-title">Anonymous submissions</h2>
        </div>

        <div class="pr-emph">
            <p class="pr-emph-title"><i data-lucide="eye-off" aria-hidden="true"></i>What anonymous submission does</p>
            <p>
                When you select <strong>Submit anonymously</strong>, LIKHA does not associate that feedback record with your user account through its user association.
                The interface displays the contributor as anonymous rather than by name.
            </p>
            <p>
                Anonymity applies to the submission's contributor association within LIKHA.
                Information you voluntarily include in the submission text, or that appears inside uploaded evidence, may still identify a person.
            </p>
        </div>
    </section>

    {{-- 03 How your information is used --}}
    <section class="pr-section anim-1" id="pr-use">
        <div class="pr-head">
            <span class="pr-num">03</span>
            <h2 class="pr-title">How your information is used</h2>
        </div>

        <p class="pr-sub">Institutional problem analysis</p>
        <p class="pr-text">Submitted information can be processed by the DSS to support:</p>
        <ul class="pr-list">
            <li>Clustering related problems together</li>
            <li>Evidence analysis</li>
            <li>Severity calculation</li>
            <li>Confidence calculation</li>
            <li>Capstone opportunity generation</li>
            <li>Evaluation of generated ideas</li>
            <li>Problem and category discovery in Discover and category pages</li>
        </ul>

        <p class="pr-sub">Review and moderation</p>
        <p class="pr-text">Information is used to:</p>
        <ul class="pr-list">
            <li>Review submissions before they become visible</li>
            <li>Record reviewer actions</li>
            <li>Support office report review</li>
            <li>Track approval and rejection</li>
            <li>Record priority actions where applicable</li>
        </ul>

        <p class="pr-sub">Office confirmation</p>
        <p class="pr-text">Information is used to support:</p>
        <ul class="pr-list">
            <li>Confirmation requests on office-backed opportunities</li>
            <li>Office representative decisions</li>
            <li>Confirmation status, including pending, office-confirmed, taken and declined</li>
            <li>Historical confirmation records</li>
        </ul>

        <p class="pr-sub">Notifications</p>
        <p class="pr-text">Notifications can be generated for relevant system events, such as:</p>
        <ul class="pr-list">
            <li>A DSS idea has been generated from problems you contributed to</li>
            <li>An office report is awaiting review</li>
            <li>An office confirmation request has been submitted</li>
        </ul>
    </section>

    {{-- 04 Who can access information --}}
    <section class="pr-section anim-1" id="pr-access">
        <div class="pr-head">
            <span class="pr-num">04</span>
            <h2 class="pr-title">Who can access information</h2>
        </div>
        <p class="pr-text">
            Access is controlled by your account role and, for some areas, by a specific relationship you hold.
        </p>

        <div class="pr-access">
            <div class="pr-access-row">
                <div class="pr-access-role"><i data-lucide="user-round" aria-hidden="true"></i>Verified users</div>
                <div class="pr-access-detail">
                    Can use the normal LIKHA features — browsing, searching, submitting, saved ideas, votes and
                    comments — and can see their own account information.
                </div>
            </div>
            <div class="pr-access-row">
                <div class="pr-access-role"><i data-lucide="building-2" aria-hidden="true"></i>Office representatives</div>
                <div class="pr-access-detail">
                    Can access the office confirmation queue and make confirmation decisions only for the offices
                    they currently represent. This is based on the office's current representative record, not on a
                    system role.
                </div>
            </div>
            <div class="pr-access-row">
                <div class="pr-access-role"><i data-lucide="clipboard-check" aria-hidden="true"></i>Reviewers</div>
                <div class="pr-access-detail">
                    Users with reviewer roles can review submissions assigned to their categories.
                    Being a reviewer does not automatically make a user an office representative.
                </div>
            </div>
            <div class="pr-access-row">
                <div class="pr-access-role"><i data-lucide="graduation-cap" aria-hidden="true"></i>Advisers</div>
                <div class="pr-access-detail">
                    Have access to adviser-specific dashboards, evaluations, reports and analytics.
                </div>
            </div>
            <div class="pr-access-row">
                <div class="pr-access-role"><i data-lucide="shield" aria-hidden="true"></i>Administrators</div>
                <div class="pr-access-detail">
                    Have broader system-management access covering feedback moderation, users, evidence, offices,
                    category assignments, priority problems and system settings.
                </div>
            </div>
        </div>
    </section>

    {{-- 05 External services --}}
    <section class="pr-section anim-1" id="pr-services">
        <div class="pr-head">
            <span class="pr-num">05</span>
            <h2 class="pr-title">External services</h2>
        </div>
        <p class="pr-text">
            LIKHA is configured with the following external services. Each entry describes what the inspected
            implementation actually does.
        </p>

        <p class="pr-sub">Google sign-in (OAuth)</p>
        <p class="pr-text">
            If you choose Google sign-in, Google provides LIKHA with your Google account identifier, name, and email
            address. LIKHA stores these values and uses the returned account information for authentication.
            LIKHA does not receive your Google password.
        </p>

        <p class="pr-sub">Ollama (AI wording enhancement)</p>
        <p class="pr-text">LIKHA can optionally use Ollama to enhance the wording of generated capstone information.</p>
        <p class="pr-text">The AI layer receives only these fields:</p>
        <ul class="pr-list">
            <li>Title</li>
            <li>Description</li>
            <li>General objective</li>
            <li>Specific objectives</li>
        </ul>
        <div class="pr-callout" style="margin-top:12px">
            The DSS remains responsible for the underlying recommendation and analysis. AI enhancement is a wording
            and presentation layer. AI enhancement is disabled by default in the inspected configuration.
        </div>

        <p class="pr-sub">Google reCAPTCHA</p>
        <p class="pr-text">
            Google reCAPTCHA is present in the project configuration but is not currently active in the application
            flow inspected.
        </p>

        <p class="pr-sub">Email</p>
        <p class="pr-text">
            Email services are used for functions such as email verification, password reset and
            authentication-related messages.
        </p>

        <p class="pr-sub">Translation</p>
        <p class="pr-text">
            LIKHA includes translation services for certain system text and idea-processing workflows.
        </p>
    </section>

    {{-- 06 Your available controls --}}
    <section class="pr-section anim-1" id="pr-controls">
        <div class="pr-head">
            <span class="pr-num">06</span>
            <h2 class="pr-title">Your available controls</h2>
        </div>

        <p class="pr-sub">Account</p>
        <ul class="pr-list">
            <li>Change your name</li>
            <li>Change your email address</li>
            <li>Change your password</li>
            <li>Delete your account</li>
            <li>Log out</li>
        </ul>
        <div class="pr-callout" style="margin-top:10px">
            Changing an email address requires email verification again.
        </div>

        <p class="pr-sub">Contributions</p>
        <ul class="pr-list">
            <li>Vote or remove your vote on a problem</li>
            <li>Remove a Saved Idea</li>
            <li>Submit evidence</li>
            <li>Add comments</li>
        </ul>

        <p class="pr-sub">Preferences</p>
        <ul class="pr-list">
            <li>Change the interface theme between light and dark</li>
        </ul>

        <div class="pr-limit" style="margin-top:16px">
            <p class="pr-limit-title"><i data-lucide="info" aria-hidden="true"></i>Currently unavailable</p>
            <p>
                LIKHA does not currently provide built-in tools for bulk data export, downloading all personal
                data, or a dedicated consent-management interface.
            </p>
        </div>
    </section>

    {{-- 07 Account deletion --}}
    <section class="pr-section anim-1" id="pr-deletion">
        <div class="pr-head">
            <span class="pr-num">07</span>
            <h2 class="pr-title">Account deletion</h2>
        </div>
        <p class="pr-text">
            Account deletion requires your current password to be confirmed. When you delete your account:
        </p>
        <ul class="pr-list">
            <li>You are logged out.</li>
            <li>The account record is deleted, if the existing database constraints allow the deletion to proceed.</li>
            <li>Related votes, comments, saved ideas and submissions linked to your account may also be removed through the database relationships.</li>
        </ul>

        <div class="pr-emph" style="margin-top:14px">
            <p class="pr-emph-title"><i data-lucide="triangle-alert" aria-hidden="true"></i>Some accounts cannot currently be deleted</p>
            <p>
                Deletion is currently blocked where an existing office-representative record or an office
                confirmation record still references the account. In those cases the account cannot be removed
                until those relationships no longer apply.
            </p>
        </div>

        <div class="pr-limit" style="margin-top:14px">
            <p class="pr-limit-title"><i data-lucide="hard-drive" aria-hidden="true"></i>Uploaded files</p>
            <p>
                Uploaded evidence and attachment files stored on disk are not currently removed automatically when
                their database records are deleted.
            </p>
        </div>
    </section>

    {{-- 08 Retention and records --}}
    <section class="pr-section anim-1" id="pr-retention">
        <div class="pr-head">
            <span class="pr-num">08</span>
            <h2 class="pr-title">Retention and records</h2>
        </div>

        <div class="pr-emph">
            <p class="pr-emph-title"><i data-lucide="clock" aria-hidden="true"></i>No fixed retention period is currently defined</p>
            <p>
                No fixed retention period is currently defined in the LIKHA application.
                Retention periods and institutional record-management policies are not currently configured in the application.
            </p>
        </div>

        <p class="pr-sub">Records that remain after their workflow completes</p>
        <ul class="pr-list">
            <li>Notifications remain after being marked as read.</li>
            <li>Rejected submissions remain as records.</li>
            <li>Generated idea evaluations remain as system artifacts.</li>
            <li>Completed confirmation requests remain in their historical state.</li>
            <li>Stale confirmation requests remain recorded.</li>
        </ul>
    </section>

    {{-- 09 Uploaded evidence --}}
    <section class="pr-section anim-1" id="pr-evidence">
        <div class="pr-head">
            <span class="pr-num">09</span>
            <h2 class="pr-title">Uploaded evidence</h2>
        </div>
        <p class="pr-text">
            You can upload evidence as part of an institutional problem submission. The system processes
            uploaded files using file-type validation and randomized filenames.
        </p>
        <p class="pr-text">
            Because uploaded evidence and attachment files stored on disk are not currently removed automatically
            when their database records are deleted, evidence is not removed when an account is deleted.
        </p>

        <div class="pr-emph" style="margin-top:14px">
            <p class="pr-emph-title"><i data-lucide="lightbulb" aria-hidden="true"></i>Before you upload</p>
            <p>
                Before uploading evidence, avoid including unnecessary personal or sensitive information that is
                unrelated to the institutional problem being reported.
            </p>
        </div>
    </section>

    {{-- 10 Privacy questions --}}
    <section class="pr-section anim-1" id="pr-contact">
        <div class="pr-head">
            <span class="pr-num">10</span>
            <h2 class="pr-title">Privacy questions</h2>
        </div>

        <div class="pr-limit">
            <p class="pr-limit-title"><i data-lucide="user-round-x" aria-hidden="true"></i>Privacy contact not yet configured</p>
            <p>
                LIKHA currently does not contain a dedicated privacy officer, privacy email address, or formal
                privacy-contact workflow.
            </p>
        </div>

        <p class="pr-callout" style="margin-top:14px">
            If LIKHA is deployed institutionally, the designated institutional privacy contact and procedures for
            privacy-related requests should be provided here. That contact is an institutional configuration item
            and is not currently part of the application.
        </p>
    </section>

    {{-- Closing principle --}}
    <section class="pr-closing anim-1">
        <p class="pr-closing-title">Privacy information describes how LIKHA currently handles information.</p>
        <p>
            It does not replace institutional privacy policies, procedures, or applicable requirements that may be
            established separately. Where a policy is not yet configured, this page says so rather than describing
            an arrangement that does not exist.
        </p>
    </section>

</div>
@endsection