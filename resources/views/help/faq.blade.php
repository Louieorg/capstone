@extends('layouts.app')

@section('title', 'Frequently Asked Questions')
@section('subtitle', 'Short answers about problems, opportunities, confirmation and the DSS')

@section('content')
@include('layouts.partials.design-system')

{{-- FAQ. Interaction is plain Alpine, matching the pattern used elsewhere in the
     app. Styles are scoped to this page so the global shell stays untouched. --}}
<style>
    .faq-shell { display: flex; flex-direction: column; gap: 16px; }

    /* Segmented category control */
    .faq-tabs {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 6px;
        border: 1px solid var(--border); border-radius: 12px;
        background: var(--surface);
    }
    .faq-tab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 13px; border-radius: 9px;
        border: 1px solid transparent; background: transparent;
        color: var(--text2); cursor: pointer;
        font-family: 'DM Sans', sans-serif;
        font-size: 12.5px; font-weight: 600;
        transition: background .15s, color .15s, border-color .15s;
    }
    .faq-tab:hover { color: var(--text); background: var(--surface2); }
    .faq-tab:focus-visible { outline: 2px solid var(--amber); outline-offset: 1px; }
    .faq-tab[aria-selected="true"] {
        background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber);
    }
    .faq-tab i[data-lucide] { width: 14px; height: 14px; flex-shrink: 0; }

    /* Accordion */
    .faq-list { display: flex; flex-direction: column; gap: 10px; }

    .faq-item {
        border: 1px solid var(--border); border-radius: 12px;
        background: var(--surface); overflow: hidden;
    }
    .faq-q {
        display: flex; align-items: center; gap: 12px; width: 100%;
        padding: 14px 16px; border: 0; background: transparent;
        text-align: left; cursor: pointer; color: var(--text);
        font-family: 'DM Sans', sans-serif; font-size: 13.5px; font-weight: 600;
    }
    .faq-q:hover { background: var(--surface2); }
    .faq-q:focus-visible { outline: 2px solid var(--amber); outline-offset: -2px; }
    .faq-q-text { flex: 1; min-width: 0; }
    .faq-chevron {
        width: 16px; height: 16px; flex-shrink: 0; color: var(--muted);
        transition: transform .2s;
    }
    .faq-item.is-open .faq-chevron { transform: rotate(180deg); color: var(--amber); }

    .faq-a { border-top: 1px solid var(--border); }
    .faq-a p {
        margin: 0; padding: 14px 16px;
        font-size: 13px; line-height: 1.75; color: var(--text2);
    }
    .faq-a p + p { padding-top: 0; }
    .faq-a strong { color: var(--text); font-weight: 600; }

    .faq-cta {
        display: flex; flex-wrap: wrap; gap: 10px;
        padding: 14px 16px; border-top: 1px solid var(--border);
        background: var(--surface2);
    }

    .faq-principle {
        border: 1px solid var(--amber-mid); border-radius: 14px;
        background: var(--amber-dim); padding: 18px 20px;
    }
    .faq-principle-line {
        font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 800;
        letter-spacing: .02em; margin: 0; line-height: 1.35;
    }
    .faq-principle p:last-child {
        margin: 10px 0 0; font-size: 12.5px; line-height: 1.7; color: var(--text2);
    }
</style>

<div
    class="faq-shell mx-auto max-w-4xl"
    x-data="{
        active: 'getting-started',
        open: {},
        toggle(key) { this.open[key] = !this.open[key]; },
        isOpen(key) { return this.open[key] === true; },
    }">

    {{-- Short orientation --}}
    <div class="lk-card anim-1" style="padding:16px 18px;display:flex;flex-wrap:wrap;align-items:center;gap:14px">
        <p class="flex-1" style="margin:0;font-size:13px;line-height:1.7;color:var(--text2);min-width:220px">
            Answers about how LIKHA works today. For the full walkthrough, read the
            <a href="{{ route('help.user-guide') }}" style="color:var(--amber);font-weight:600;text-decoration:none">User Guide</a>.
        </p>
        <button type="button" class="btn-ghost text-sm" @click="$root.querySelectorAll('.faq-item').forEach((el) => { const q = el.querySelector('.faq-q'); if (q && q.getAttribute('aria-expanded') === 'false') { q.click(); } })">
            <i data-lucide="chevrons-down-up" class="h-4 w-4" aria-hidden="true"></i>
            Expand this category
        </button>
    </div>

    {{-- Segmented category control. Rendered server-side so the labels are real,
         selectable text and do not depend on JavaScript to exist. --}}
    <div class="faq-tabs anim-1" role="tablist" aria-label="FAQ categories">
        <button type="button" class="faq-tab" role="tab" data-tab="getting-started"
                aria-controls="faq-panel-getting-started"
                :aria-selected="(active === 'getting-started').toString()"
                @click="active = 'getting-started'">
            <i data-lucide="rocket" aria-hidden="true"></i>
            <span>Getting Started</span>
        </button>
        <button type="button" class="faq-tab" role="tab" data-tab="problems"
                aria-controls="faq-panel-problems"
                :aria-selected="(active === 'problems').toString()"
                @click="active = 'problems'">
            <i data-lucide="file-text" aria-hidden="true"></i>
            <span>Problems &amp; Evidence</span>
        </button>
        <button type="button" class="faq-tab" role="tab" data-tab="opportunities"
                aria-controls="faq-panel-opportunities"
                :aria-selected="(active === 'opportunities').toString()"
                @click="active = 'opportunities'">
            <i data-lucide="lightbulb" aria-hidden="true"></i>
            <span>Capstone Opportunities</span>
        </button>
        <button type="button" class="faq-tab" role="tab" data-tab="confirmation"
                aria-controls="faq-panel-confirmation"
                :aria-selected="(active === 'confirmation').toString()"
                @click="active = 'confirmation'">
            <i data-lucide="send" aria-hidden="true"></i>
            <span>Office Confirmation</span>
        </button>
        <button type="button" class="faq-tab" role="tab" data-tab="ai"
                aria-controls="faq-panel-ai"
                :aria-selected="(active === 'ai').toString()"
                @click="active = 'ai'">
            <i data-lucide="cpu" aria-hidden="true"></i>
            <span>AI &amp; The System</span>
        </button>
    </div>

    {{-- GETTING STARTED --}}
    <section class="faq-list anim-1" role="tabpanel" id="faq-panel-getting-started" x-show="active === 'getting-started'">
        <div class="faq-item" data-category="getting-started">
            <button type="button" class="faq-q" @click="toggle('getting-started')" :aria-expanded="isOpen('getting-started').toString()">
                <span class="faq-q-text">What is LIKHA?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('getting-started')" x-collapse>
                <p>LIKHA is a Decision Support System for institutional problems and capstone opportunities. It collects problems reported by people in the institution, evaluates them against the evidence available, and surfaces project concepts that come from problems which recur and matter.</p>
            </div>
        </div>

        <div class="faq-item" data-category="getting-started">
            <button type="button" class="faq-q" @click="toggle('getting-started')" :aria-expanded="isOpen('getting-started').toString()">
                <span class="faq-q-text">Who can use LIKHA?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('getting-started')" x-collapse>
                <p>Students, office representatives, reviewers, advisers and administrators can each use LIKHA according to what their account is responsible for. You only see the areas that apply to you.</p>
            </div>
        </div>

        <div class="faq-item" data-category="getting-started">
            <button type="button" class="faq-q" @click="toggle('getting-started')" :aria-expanded="isOpen('getting-started').toString()">
                <span class="faq-q-text">What can I do in LIKHA?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('getting-started')" x-collapse>
                <p>As a student you can browse and search institutional problems, submit a problem with evidence, follow capstone opportunities, save ideas you are interested in, and request office confirmation. If you represent an office you can also answer confirmation requests.</p>
                <div class="faq-cta">
                    <a href="{{ route('home') }}" class="btn-ghost text-sm">Go to Home Feed</a>
                    <a href="{{ route('help.user-guide') }}" class="btn-ghost text-sm">Read the User Guide</a>
                </div>
            </div>
        </div>

        <div class="faq-item" data-category="getting-started">
            <button type="button" class="faq-q" @click="toggle('getting-started')" :aria-expanded="isOpen('getting-started').toString()">
                <span class="faq-q-text">What is an institutional problem?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('getting-started')" x-collapse>
                <p>It is a recurring obstacle in how the institution works, rather than a project idea. A process that is slow or unclear, a record that is never kept, a service that is hard to access, or equipment that is unavailable without asking. If it happens repeatedly and affects people beyond you, it is a candidate.</p>
            </div>
        </div>
    </section>

    {{-- PROBLEMS & EVIDENCE --}}
    <section class="faq-list anim-1" role="tabpanel" id="faq-panel-problems" x-show="active === 'problems'">
        <div class="faq-item" data-category="problems">
            <button type="button" class="faq-q" @click="toggle('problems')" :aria-expanded="isOpen('problems').toString()">
                <span class="faq-q-text">What should I submit?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('problems')" x-collapse>
                <p>A real, recurring problem you have personally encountered or can document. Describe what happens today, how often it happens, who it affects and what the impact is. The more concrete the description, the better it can be evaluated.</p>
            </div>
        </div>

        <div class="faq-item" data-category="problems">
            <button type="button" class="faq-q" @click="toggle('problems')" :aria-expanded="isOpen('problems').toString()">
                <span class="faq-q-text">Why is supporting evidence important?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('problems')" x-collapse>
                <p>Evidence establishes that the problem is real rather than a one-off impression. It is reviewed alongside the report and appears with the problem where available, so other people can judge the problem for themselves.</p>
            </div>
        </div>

        <div class="faq-item" data-category="problems">
            <button type="button" class="faq-q" @click="toggle('problems')" :aria-expanded="isOpen('problems').toString()">
                <span class="faq-q-text">What happens after I submit a problem?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('problems')" x-collapse>
                <p>A problem you submit as yourself enters pending review. Once approved it becomes visible to others and becomes available to the DSS for evaluation. A report filed on behalf of an office is reviewed by a reviewer assigned to that office's categories.</p>
            </div>
        </div>

        <div class="faq-item" data-category="problems">
            <button type="button" class="faq-q" @click="toggle('problems')" :aria-expanded="isOpen('problems').toString()">
                <span class="faq-q-text">What happens if my submission is similar to another problem?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('problems')" x-collapse>
                <p>LIKHA checks your report against existing problems and shows you the ones that resemble it. At that point you can support an existing problem instead of adding a near-duplicate, or choose to submit your report anyway.</p>
            </div>
        </div>

        <div class="faq-item" data-category="problems">
            <button type="button" class="faq-q" @click="toggle('problems')" :aria-expanded="isOpen('problems').toString()">
                <span class="faq-q-text">What is the difference between a Community submission and an Office submission?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('problems')" x-collapse>
                <p>A Community submission is filed as yourself and enters ordinary review. An Office submission is filed on behalf of an office you currently represent; it is qualified for that office without waiting on the usual community thresholds, and it is tied to that office.</p>
                <p>You can only submit on behalf of an office you currently represent. If you stop representing an office, that option is no longer offered.</p>
            </div>
        </div>
    </section>

    {{-- CAPSTONE OPPORTUNITIES --}}
    <section class="faq-list anim-1" role="tabpanel" id="faq-panel-opportunities" x-show="active === 'opportunities'">
        <div class="faq-item" data-category="opportunities">
            <button type="button" class="faq-q" @click="toggle('opportunities')" :aria-expanded="isOpen('opportunities').toString()">
                <span class="faq-q-text">How does LIKHA produce a capstone opportunity?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('opportunities')" x-collapse>
                <p>Problems that recur and carry enough support are grouped together. The DSS evaluates the group and produces a project concept from it. If an office stands behind the underlying problem, the result is an Office-Backed Opportunity; if it comes only from community reports, it is a Community-Generated Idea.</p>
            </div>
        </div>

        <div class="faq-item" data-category="opportunities">
            <button type="button" class="faq-q" @click="toggle('opportunities')" :aria-expanded="isOpen('opportunities').toString()">
                <span class="faq-q-text">What is a Community-Generated Idea?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('opportunities')" x-collapse>
                <p>A project concept the DSS generated from community-reported problems, with no office attached. It is an idea for you to develop, not an opportunity an office has offered.</p>
            </div>
        </div>

        <div class="faq-item" data-category="opportunities">
            <button type="button" class="faq-q" @click="toggle('opportunities')" :aria-expanded="isOpen('opportunities').toString()">
                <span class="faq-q-text">What is an Office-Backed Opportunity?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('opportunities')" x-collapse>
                <p>An approved institutional problem that an office has marked as suitable capstone material. The office is named, and its representative is the person you deal with for confirmation.</p>
            </div>
        </div>

        <div class="faq-item" data-category="opportunities">
            <button type="button" class="faq-q" @click="toggle('opportunities')" :aria-expanded="isOpen('opportunities').toString()">
                <span class="faq-q-text">What do Severity and Confidence mean?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('opportunities')" x-collapse>
                <p>Severity is how significant or urgent the problem is judged to be. Confidence is how much the supporting evidence can be trusted. They are separate: a problem can be severe and still have low confidence, for example when one person reported a critical issue and nobody else has supported it yet.</p>
            </div>
        </div>

        <div class="faq-item" data-category="opportunities">
            <button type="button" class="faq-q" @click="toggle('opportunities')" :aria-expanded="isOpen('opportunities').toString()">
                <span class="faq-q-text">Does saving an idea reserve it?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('opportunities')" x-collapse>
                <p>No. A Saved Idea is a personal bookmark. It does not reserve the idea, it is not office confirmation, and it does not establish ownership. Another group may still work on the same idea.</p>
            </div>
        </div>
    </section>

    {{-- OFFICE CONFIRMATION --}}
    <section class="faq-list anim-1" role="tabpanel" id="faq-panel-confirmation" x-show="active === 'confirmation'">
        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">What does Confirmation Requested mean?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>Your request has been sent to the office's representative and is waiting for a decision. The opportunity stays available while it waits, and other groups may also request it.</p>
            </div>
        </div>

        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">Why should I consult the office representative?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>Because the office knows the problem you are building on. Talking to the representative before you request confirmation helps you understand what is realistic, what data exists, and whether your direction matches what the office actually needs.</p>
            </div>
        </div>

        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">Does LIKHA record the consultation?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>No. Consultation happens outside LIKHA. The system does not record a consultation, does not verify that one took place, and a Confirmation Request does not reserve the opportunity for you.</p>
            </div>
        </div>

        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">What happens when an office confirms?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>The request becomes Office-Confirmed and the opportunity becomes Taken for the group that requested it. Other groups can no longer claim it. Only the office's current representative can confirm or decline.</p>
            </div>
        </div>

        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">What happens when an office declines?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>The request is Declined and the opportunity returns to Available, so another group can request it. You are free to request it again later.</p>
            </div>
        </div>

        <div class="faq-item" data-category="confirmation">
            <button type="button" class="faq-q" @click="toggle('confirmation')" :aria-expanded="isOpen('confirmation').toString()">
                <span class="faq-q-text">What does Taken mean?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('confirmation')" x-collapse>
                <p>Taken means the opportunity has been confirmed for a group and is no longer available to claim. It is not the same as being reserved by a request that is still waiting.</p>
            </div>
        </div>
    </section>

    {{-- AI & THE SYSTEM --}}
    <section class="faq-list anim-1" role="tabpanel" id="faq-panel-ai" x-show="active === 'ai'">
        <div class="faq-principle" style="margin-bottom:14px">
            <p class="faq-principle-line" style="color:var(--text)">THE DSS DECIDES.</p>
            <p class="faq-principle-line" style="color:var(--amber)">THE AI EXPLAINS.</p>
            <p>Everything below follows from that distinction.</p>
        </div>

        <div class="faq-item" data-category="ai">
            <button type="button" class="faq-q" @click="toggle('ai')" :aria-expanded="isOpen('ai').toString()">
                <span class="faq-q-text">Does AI generate my capstone idea?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('ai')" x-collapse>
                <p>No. The concept itself comes from rule-based DSS processing over the reports, votes and other institutional evidence collected for that problem.</p>
            </div>
        </div>

        <div class="faq-item" data-category="ai">
            <button type="button" class="faq-q" @click="toggle('ai')" :aria-expanded="isOpen('ai').toString()">
                <span class="faq-q-text">What does the AI-enhanced wording do?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('ai')" x-collapse>
                <p>It is an optional panel that rewrites how a title, description or objective is worded so it reads more clearly. The DSS scores and recommendation underneath it stay exactly the same. You can show or hide it at any time.</p>
            </div>
        </div>

        <div class="faq-item" data-category="ai">
            <button type="button" class="faq-q" @click="toggle('ai')" :aria-expanded="isOpen('ai').toString()">
                <span class="faq-q-text">Can AI change the DSS decision?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('ai')" x-collapse>
                <p>No. AI does not decide qualification, severity, confidence, thresholds, office scope, or whether an opportunity exists. It only affects presentation.</p>
            </div>
        </div>

        <div class="faq-item" data-category="ai">
            <button type="button" class="faq-q" @click="toggle('ai')" :aria-expanded="isOpen('ai').toString()">
                <span class="faq-q-text">Who determines whether an opportunity qualifies?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('ai')" x-collapse>
                <p>The DSS does, using the configured rules and the evidence available: how many reports there are, how much support they have, frequency, impact, severity and confidence. For an office-backed opportunity, the office's own mark on the problem is what attaches the office to it.</p>
            </div>
        </div>

        <div class="faq-item" data-category="ai">
            <button type="button" class="faq-q" @click="toggle('ai')" :aria-expanded="isOpen('ai').toString()">
                <span class="faq-q-text">Why does LIKHA use a DSS?</span>
                <i data-lucide="chevron-down" class="faq-chevron" aria-hidden="true"></i>
            </button>
            <div class="faq-a" x-show="isOpen('ai')" x-collapse>
                <p>So the suggestions are traceable to real institutional evidence instead of guesswork. Every opportunity points back to problems people actually reported, and you can see which ones and why.</p>
            </div>
        </div>
    </section>

</div>
@endsection