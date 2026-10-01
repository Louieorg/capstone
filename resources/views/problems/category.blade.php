@extends('layouts.app')

@section('title', $category . ' Capstone Opportunities')
@section('subtitle', 'Capstone opportunities identified from real institutional problems and DSS analysis.')

@section('content')
@php
  $highlightedIdeaTitle = request('idea');
@endphp
<style>
/* ── All color decisions live here, keyed to html.dark ── */

/* DARK (default — html has .dark class) */
html.dark {
  --surface:      #13141a;
  --surface2:     #1a1b23;
  --border:       rgba(255,255,255,0.07);
  --border-h:     rgba(251,176,52,0.28);
  --text:         #f0f0f5;
  --text2:        #9a9bb0;
  --text3:        #5e6175;
  --amber:        #fbb034;
  --adim:         rgba(251,176,52,0.10);
  --amid:         rgba(251,176,52,0.22);
  --green:        #5fcd8a;
  --green-bg:     rgba(95,205,138,0.10);
  --green-b:      rgba(95,205,138,0.22);
  --red:          #f87171;
  --red-bg:       rgba(248,113,113,0.10);
  --red-b:        rgba(248,113,113,0.22);
  --blue:         #60a5fa;
  --blue-bg:      rgba(96,165,250,0.10);
  --blue-b:       rgba(96,165,250,0.20);
  --bar-track:    rgba(255,255,255,0.06);
  --acc-hover:    rgba(255,255,255,0.04);
  --acc-body:     rgba(255,255,255,0.03);
}

/* LIGHT */
html:not(.dark) {
  --surface:      #ffffff;
  --surface2:     #f5f4f0;
  --border:       rgba(0,0,0,0.08);
  --border-h:     rgba(186,117,23,0.35);
  --text:         #111014;
  --text2:        #5a5870;
  --text3:        #9a97b0;
  --amber:        #b57318;
  --adim:         rgba(186,117,23,0.08);
  --amid:         rgba(186,117,23,0.18);
  --green:        #15803d;
  --green-bg:     rgba(22,163,74,0.08);
  --green-b:      rgba(22,163,74,0.20);
  --red:          #dc2626;
  --red-bg:       rgba(220,38,38,0.08);
  --red-b:        rgba(220,38,38,0.20);
  --blue:         #1d4ed8;
  --blue-bg:      rgba(29,78,216,0.08);
  --blue-b:       rgba(29,78,216,0.18);
  --bar-track:    rgba(0,0,0,0.07);
  --acc-hover:    rgba(0,0,0,0.02);
  --acc-body:     rgba(0,0,0,0.015);
}

/* ── Animations ── */
@keyframes fadeInUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.anim-1 { animation: fadeInUp .5s ease both; }
.anim-2 { animation: fadeInUp .5s .08s ease both; }
.anim-3 { animation: fadeInUp .5s .16s ease both; }
.anim-4 { animation: fadeInUp .5s .24s ease both; }

/* ── Base card ── */
.lk-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  transition: border-color .2s, transform .2s;
}
.lk-card:hover { border-color: var(--border-h); }
.lk-card.idea-highlight {
  border-color: var(--amid);
  box-shadow: 0 0 0 4px var(--adim);
}

/* ── Scope filter (page-level; this view does not load the design-system partial) ── */
.sort-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.sort-pill {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 999px;
  font-size: 12.5px; font-weight: 600; text-decoration: none;
  background: var(--surface); border: 1px solid var(--border); color: var(--text2);
  transition: all .15s;
}
.sort-pill:hover { border-color: var(--amid); color: var(--amber); background: var(--adim); }
.sort-pill.active { background: var(--adim); border-color: var(--amid); color: var(--amber); }

/* ── Problem → DSS → Opportunity flow ── */
.co-flow {
  display: grid; grid-template-columns: 1fr auto 1fr auto 1fr;
  align-items: stretch; gap: 10px;
}
.co-flow-step {
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 14px; padding: 16px 16px 15px; min-width: 0;
}
.co-flow-step.is-accent {
  background: var(--adim); border-color: var(--amid);
}
.co-flow-step.is-result {
  background: var(--surface); border-color: var(--amid);
  box-shadow: 0 0 0 3px var(--adim);
}
.co-flow-kicker {
  font-size: 10px; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--text3); margin-bottom: 7px;
}
.co-flow-step.is-accent .co-flow-kicker,
.co-flow-step.is-result .co-flow-kicker { color: var(--amber); }
.co-flow-title {
  font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
  color: var(--text); line-height: 1.35; margin-bottom: 6px;
}
.co-flow-copy { font-size: 12px; color: var(--text2); line-height: 1.6; }
.co-flow-arrow {
  display: flex; align-items: center; justify-content: center;
  color: var(--amber); font-size: 18px; font-weight: 700;
}
@media (max-width: 760px) {
  .co-flow { grid-template-columns: 1fr; }
  .co-flow-arrow { transform: rotate(90deg); padding: 2px 0; }
}

/* ── Accordion ── */
.accordion-btn {
  display: flex; justify-content: space-between; align-items: center;
  width: 100%; padding: 16px 20px; background: none; border: none;
  cursor: pointer; text-align: left; font-size: 13.5px; font-weight: 600;
  color: var(--text); font-family: 'DM Sans', sans-serif;
  transition: background .15s;
}
.accordion-btn:hover { background: var(--acc-hover); }
.accordion-icon {
  width: 16px; height: 16px; flex-shrink: 0;
  color: var(--text3); transition: transform .25s;
}
.accordion-btn[aria-expanded="true"] .accordion-icon { transform: rotate(180deg); }
.accordion-body {
  padding: 16px 20px 20px;
  border-top: 1px solid var(--border);
  background: var(--acc-body);
}

/* ── Factor bars ── */
.factor-bar {
  height: 5px; border-radius: 999px;
  background: var(--bar-track); overflow: hidden; margin-top: 5px;
}
.factor-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(to right, #fbb034, #f97316);
  transition: width .6s ease;
}

/* ── Badge base ── */
.lk-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.badge-amber { background: var(--adim); color: var(--amber); border-color: var(--amid); }
.badge-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.badge-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
.badge-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
.badge-muted {
  background: var(--adim); border-color: var(--border);
  color: var(--text3);
}

/* ── Stars ── */
.stars { color: var(--amber); letter-spacing: .05em; }

/* ── DSS evaluation ── */
.idea-evaluation {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1px;
  margin: 0 0 18px;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--border);
}
.idea-evaluation-score {
  min-width: 0;
  padding: 12px 14px;
  background: var(--surface2);
}
.idea-evaluation-label {
  display: block;
  margin-bottom: 5px;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--text3);
}
.idea-evaluation-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
  padding: 12px 14px;
  border-radius: 12px;
  background: var(--adim);
  border: 1px solid var(--amid);
}
@media (max-width: 640px) {
  .idea-evaluation { grid-template-columns: 1fr 1fr; }
  .idea-evaluation-summary { align-items: flex-start; flex-direction: column; }
}

/* ── Evidence stat grid ── */
.co-evidence {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px; margin-bottom: 16px;
}
.co-evidence-item {
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 12px; padding: 12px 14px;
}
.co-evidence-value {
  font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700;
  color: var(--text); line-height: 1.1;
}
.co-evidence-label {
  margin-top: 4px; font-size: 10.5px; font-weight: 600;
  letter-spacing: .06em; text-transform: uppercase; color: var(--text3);
}

/* ── Section heading ── */
.co-section-head {
  display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
  flex-wrap: wrap;
}
.co-section-dot {
  width: 8px; height: 8px; border-radius: 50%; background: var(--amber); flex-shrink: 0;
}
.co-section-title {
  font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: var(--text);
  margin: 0;
}
.co-section-sub { font-size: 12px; color: var(--text3); }

/* ── Form inputs ── */
.lk-input {
  width: 100%; background: var(--surface2);
  border: 1px solid var(--border); border-radius: 10px;
  padding: 9px 13px; font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.lk-input::placeholder { color: var(--text3); }
.lk-input:focus {
  border-color: var(--amid);
  box-shadow: 0 0 0 3px var(--adim);
}
select.lk-input {
  background: var(--surface2);
  color: var(--text);
  color-scheme: light dark;
}
select.lk-input option {
  background: var(--surface);
  color: var(--text);
}
select.lk-input:invalid {
  color: var(--text3);
}

/* ── Adviser review form ── */
.review-form {
  margin: 0 28px 28px;
  padding: 20px 22px;
  border-radius: 14px;
  background: var(--surface2);
  border: 1px solid var(--border);
}
.review-form-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--amber);
}
.review-form-head-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--amber);
  flex-shrink: 0;
}
.review-form-copy {
  font-size: 12.5px;
  color: var(--text3);
  line-height: 1.6;
  margin-bottom: 16px;
}
.review-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 640px) {
  .review-grid {
    grid-template-columns: 1fr;
  }
}
.review-field-label {
  display: block;
  margin-bottom: 5px;
  font-size: 11.5px;
  color: var(--text3);
}

/* Nested horizontal insets are desktop-sized. On phones they stack with the
   card padding and the page gutter, so drop them to keep a usable measure. */
@media (max-width: 640px) {
  .review-form { margin: 0 0 16px; padding: 16px 14px; }
  .co-adviser { margin: 0 0 16px; padding: 14px; }
}

/* ── Buttons ── */
.btn-amber {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 20px; border-radius: 10px;
  background: var(--amber); color: #0a0b0f;
  font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif;
  border: none; cursor: pointer; text-decoration: none;
  transition: all .18s;
}
html.dark .btn-amber { background: #fbb034; }
.btn-amber:hover { filter: brightness(1.1); transform: translateY(-1px); }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 20px; border-radius: 10px;
  background: var(--surface2); border: 1px solid var(--border);
  color: var(--text2); font-size: 13px; font-weight: 600;
  font-family: 'DM Sans', sans-serif; cursor: pointer;
  transition: all .18s;
}
.btn-ghost:hover { border-color: var(--amid); color: var(--text); }

/* ── Section label ── */
.sec-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: .1em;
  text-transform: uppercase; color: var(--text3); margin-bottom: 14px;
}

/* ── Report row ── */
.report-row {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 20px;
  transition: border-color .2s, transform .2s;
}
.report-row:hover { border-color: var(--border-h); transform: translateY(-2px); }

/* ── Comparison table ── */
.cmp-table th {
  font-size: 10.5px; text-transform: uppercase; letter-spacing: .07em;
  font-weight: 600; padding: 10px 16px; color: var(--text3);
  background: var(--surface2); border-bottom: 1px solid var(--border);
}
.cmp-table td {
  padding: 10px 16px; font-size: 13px;
  border-bottom: 1px solid var(--border); color: var(--text2);
}
.cmp-table tr:last-child td { border-bottom: none; }
.cmp-table tr:hover td { background: var(--acc-hover); }
.cmp-winner { color: var(--green); font-weight: 700; }

/* ── AI wording assistance (optional, secondary to the DSS result) ── */
.co-ai {
  margin-top: 20px; padding: 14px 16px 16px; border-radius: 12px;
  background: var(--surface2); border: 1px solid var(--border);
  border-left: 2px solid var(--amid);
}
.co-ai-head {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px; margin-bottom: 10px;
}
.co-ai-note { font-size: 11.5px; color: var(--text3); line-height: 1.6; margin-bottom: 12px; }
.co-ai-toggle {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 11px; border-radius: 999px;
  background: var(--adim); border: 1px solid var(--amid);
  color: var(--amber); font-family: 'DM Sans', sans-serif;
  font-size: 11.5px; font-weight: 600; line-height: 1; cursor: pointer;
  transition: background .15s, transform .15s;
}
.co-ai-toggle:hover { background: var(--amid); }
.co-ai-toggle-icon { width: 13px; height: 13px; flex-shrink: 0; transition: transform .25s; }
.co-ai-toggle[aria-expanded="true"] .co-ai-toggle-icon { transform: rotate(180deg); }

/* ── Cluster explanation: AI summary of the DSS's own evidence ── */
.co-synth-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 16px; padding: 20px 22px 18px;
}
.co-synth-summary { font-size: 14.5px; color: var(--text); line-height: 1.75; margin: 0; }
.co-synth-label {
  font-family: 'Sora', sans-serif; font-size: 11px; font-weight: 700;
  letter-spacing: .09em; text-transform: uppercase; color: var(--text3);
  margin: 18px 0 8px;
}
.co-synth-list {
  display: flex; flex-direction: column; gap: 7px; margin: 0; padding: 0; list-style: none;
}
.co-synth-list li { display: flex; gap: 8px; font-size: 13px; color: var(--text2); line-height: 1.65; }
.co-synth-bullet { color: var(--amber); flex-shrink: 0; margin-top: 2px; }
.co-synth-exp { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }
.co-synth-exp-title { font-size: 13.5px; font-weight: 700; color: var(--text); margin: 0 0 5px; }
.co-synth-exp-body { font-size: 13px; color: var(--text2); line-height: 1.7; margin: 0; }
.co-synth-note {
  margin: 18px 0 0; padding-top: 12px; border-top: 1px solid var(--border);
  font-size: 11.5px; color: var(--text3); line-height: 1.6;
}

/* ── Adviser review (separate layer) ── */
.co-adviser {
  margin: 0 28px 28px; padding: 16px 20px; border-radius: 12px;
  background: var(--blue-bg); border: 1px solid var(--blue-b);
}
.co-adviser-kicker {
  font-size: 12px; font-weight: 600; color: var(--blue);
  margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
}
.co-adviser-dot {
  width: 7px; height: 7px; border-radius: 50%; background: var(--blue); display: inline-block;
}

/* ── Next steps ── */
.co-steps {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
}
.co-step {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 18px 16px;
}
.co-step-num {
  font-family: 'Sora', sans-serif; font-size: 12px; font-weight: 700;
  letter-spacing: .1em; color: var(--amber); margin-bottom: 8px;
}
.co-step-title {
  font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
  color: var(--text); margin-bottom: 6px;
}
.co-step-copy { font-size: 12.5px; color: var(--text2); line-height: 1.65; }
@media (max-width: 640px) {
  .co-steps { grid-template-columns: 1fr; }
}
</style>

<div class="max-w-4xl mx-auto" style="display:flex;flex-direction:column;gap:20px">

  {{-- ══ SCOPE ══ — slim, uncarded filter row. The page bar above already
       names the category, so the old large hero header repeated it. --}}
  <div class="anim-1" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
    <span style="font-size:10px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--muted2)">Scope</span>
    <div class="sort-pills" role="navigation" aria-label="Opportunity scope">
      @foreach ([
        'all' => 'All',
        'office' => 'Office-Backed',
        'community' => 'Community',
      ] as $scope => $label)
        <a
          href="{{ request()->fullUrlWithQuery(['scope' => $scope === 'all' ? null : $scope, 'idea' => null]) }}"
          @class(['sort-pill', 'active' => $activeScope === $scope])
        >{{ $label }}</a>
      @endforeach
    </div>
  </div>

  {{-- ══ CONTEXT ══ — the one distinction worth stating up front, kept from the
       removed hero header. --}}
  <p class="anim-1 co-section-sub" style="max-width:840px">
    These are not generated ideas. Each capstone opportunity below is derived from a recurring
    institutional problem {{ $activeScope === 'office' ? 'associated with offices' : 'reported by the community' }},
    then analyzed by LIKHA's Decision Support System (DSS) to surface where a capstone project could
    create measurable impact. A capstone opportunity is a starting point — not a finished capstone:
    students still study the problem, consult the concerned office and adviser, and refine the final project.
  </p>

  {{-- ══ TOP RECOMMENDED IDEA ══ --}}
  @if(isset($topIdea))
  @php
    $isTopIdeaHighlighted = $highlightedIdeaTitle === ($topIdea['title'] ?? null);
    $topProjectName = $topIdea['project_name'] ?? null;
    $topConcept = $topIdea['concept']['primary'] ?? null;
    $topFactors = $topIdea['explanation']['factors'] ?? [];
    $topAffectedGroup = $topFactors['top_affected_group'] ?? (($topIdea['affected_groups'] ?? [])[0] ?? null);
    $topDominantProcess = $feedbacks->pluck('current_process')->filter()->countBy()->sortDesc()->keys()->first();
  @endphp
  <div x-data="{ detailsOpen:false, aiOpen:false }"
       id="idea-{{ \Illuminate\Support\Str::slug($topIdea['title'] ?? 'top-idea') }}"
       class="lk-card anim-1 {{ $isTopIdeaHighlighted ? 'idea-highlight' : '' }}"
       style="border-color:var(--amid);overflow:hidden;{{ $isTopIdeaHighlighted ? 'box-shadow:0 0 0 4px var(--adim);' : '' }}">

    {{-- Amber top line --}}
    <div style="height:3px;background:linear-gradient(to right,#fbb034,#f97316)"></div>

    <div style="padding:28px 28px 24px">

      {{-- Eyebrow --}}
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <div style="display:inline-flex;align-items:center;gap:7px;padding:5px 13px;border-radius:999px;background:var(--adim);border:1px solid var(--amid);font-size:10.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--amber)">
          <span style="width:6px;height:6px;border-radius:50%;background:var(--amber);display:inline-block"></span>
          Generated DSS Idea
        </div>
        <span style="font-size:11.5px;color:var(--text3);max-width:280px;line-height:1.5">
          Identified from a real institutional problem and DSS analysis.
        </span>
      </div>

      {{-- 1. THE PROBLEM --}}
      <section aria-label="The problem" style="margin-bottom:26px">
        <div class="co-section-head">
          <span class="co-section-dot"></span>
          <h2 class="co-section-title">The Problem</h2>
          <span class="co-section-sub">{{ $topIdea['cluster_label'] ?? Str::headline($topIdea['group'] ?? '') }}</span>
        </div>

        <p style="font-size:13.5px;color:var(--text2);line-height:1.75;margin-bottom:16px">
          {{ $topIdea['description'] ?? 'No description available' }}
        </p>

        <div class="co-evidence" style="margin-bottom:0">
          <div class="co-evidence-item">
            <div class="co-evidence-value">{{ $topIdea['reports_count'] ?? 0 }}</div>
            <div class="co-evidence-label">{{ Str::plural('Report', $topIdea['reports_count'] ?? 0) }}</div>
          </div>
          <div class="co-evidence-item">
            <div class="co-evidence-value">{{ $topIdea['support_count'] ?? 0 }}</div>
            <div class="co-evidence-label">{{ Str::plural('Support', $topIdea['support_count'] ?? 0) }}</div>
          </div>
          @if($topAffectedGroup)
            <div class="co-evidence-item">
              <div class="co-evidence-value" style="font-size:13px">{{ $topAffectedGroup }}</div>
              <div class="co-evidence-label">Affected group</div>
            </div>
          @endif
          @if($topDominantProcess)
            <div class="co-evidence-item">
              <div class="co-evidence-value" style="font-size:13px">{{ $topDominantProcess }}</div>
              <div class="co-evidence-label">Current process</div>
            </div>
          @endif
        </div>
      </section>

      {{-- 2. WHAT YOU COULD BUILD --}}
      <section aria-label="What you could build" style="margin-bottom:26px">
        <div class="co-section-head">
          <span class="co-section-dot"></span>
          <h2 class="co-section-title">What You Could Build</h2>
        </div>

        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(22px,3vw,30px);font-weight:800;line-height:1.15;color:var(--text);margin:0 0 8px">
          {{ $topProjectName ?: ($topIdea['title'] ?? 'No title available') }}
        </h2>

        @if($topConcept)
          <p style="font-size:15px;font-weight:600;color:var(--text2);line-height:1.5;margin:0 0 10px">
            {{ $topConcept }}
          </p>
        @endif

        <p style="font-size:12.5px;color:var(--text3);line-height:1.6;margin:0">
          Capstone opportunity:
          <strong style="font-weight:600;color:var(--text2)">{{ $topIdea['title'] ?? 'No title available' }}</strong>
        </p>
      </section>

      {{-- 3. WHY LIKHA SUGGESTS THIS --}}
      @if(isset($topIdea['evaluation']))
        @php
          $pri = $topIdea['priority'] ?? 'Low';
          $sev = $topIdea['severity_level'] ?? 'Low';
          $con = $topIdea['confidence_level'] ?? 'Low';
          $priBadge = $pri === 'High' ? 'badge-red' : ($pri === 'Medium' ? 'badge-amber' : 'badge-muted');
          $sevBadge = $sev === 'High' ? 'badge-red' : ($sev === 'Medium' ? 'badge-amber' : 'badge-muted');
          $conBadge = $con === 'High' ? 'badge-green' : ($con === 'Medium' ? 'badge-blue' : 'badge-muted');
          $rec = $topIdea['evaluation']['recommendation'] ?? '';
          $recClass = $rec === 'Highly Recommended' ? 'badge-green' : ($rec === 'Recommended' ? 'badge-blue' : 'badge-red');
          $overallScore = $topIdea['evaluation']['overall_score'] ?? null;
          $overallScoreText = is_numeric($overallScore) ? number_format((float) $overallScore, 2) : '—';
        @endphp

        <section aria-label="Why LIKHA suggests this" style="margin-bottom:22px">
          <div class="co-section-head">
            <span class="co-section-dot"></span>
            <h2 class="co-section-title">Why LIKHA Suggests This</h2>
            <span class="co-section-sub">Scored by the Decision Support System</span>
          </div>

          <div class="idea-evaluation" style="margin-bottom:12px">
            <div class="idea-evaluation-score">
              <span class="idea-evaluation-label">Priority</span>
              <span class="lk-badge {{ $priBadge }}">{{ $pri }}</span>
            </div>
            <div class="idea-evaluation-score">
              <span class="idea-evaluation-label">Severity</span>
              <span class="lk-badge {{ $sevBadge }}">⚠ {{ $sev }}</span>
            </div>
            <div class="idea-evaluation-score">
              <span class="idea-evaluation-label">Confidence</span>
              <span class="lk-badge {{ $conBadge }}">✓ {{ $con }}</span>
            </div>
            <div class="idea-evaluation-score">
              <span class="idea-evaluation-label">Overall evaluation</span>
              <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:var(--amber)">{{ $overallScoreText }}</span>
                <span class="lk-badge {{ $recClass }}">{{ $rec ?: '—' }}</span>
              </span>
            </div>
          </div>

          <div style="display:flex;flex-wrap:wrap;gap:7px" aria-label="Evaluation criteria">
            @foreach(['feasibility' => 'Feasibility', 'impact' => 'Impact', 'complexity' => 'Complexity', 'innovation' => 'Innovation'] as $key => $label)
              <span class="lk-badge badge-muted" aria-label="{{ $label }} {{ $topIdea['evaluation'][$key] ?? 0 }} out of 5">
                {{ $label }} {{ $topIdea['evaluation'][$key] ?? 0 }}/5
              </span>
            @endforeach
          </div>
        </section>
      @endif

      {{-- AI wording assistance (optional, clearly secondary to the DSS result) --}}
      @if(isset($topIdea['ai']))
        <section class="co-ai" aria-label="AI-enhanced wording (optional)">
          <div class="co-ai-head">
            <span class="lk-badge badge-amber">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.7 4.6 4.6 1.7-4.6 1.7L12 15.6l-1.7-4.6L5.7 9.3l4.6-1.7z"/></svg>
              AI-ENHANCED
            </span>
            <button type="button" class="co-ai-toggle"
              @click="aiOpen = !aiOpen"
              :aria-expanded="aiOpen ? 'true' : 'false'"
              aria-controls="ai-wording">
              <span x-text="aiOpen ? 'Hide' : 'Show'"></span>
              <svg class="co-ai-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
          </div>
          <div id="ai-wording" x-show="aiOpen" x-transition>
            <p class="co-ai-note">Optional wording enhancement — the DSS recommendation and scores above remain unchanged.</p>
            <p style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;line-height:1.25;color:var(--text);margin-bottom:10px">
              {{ $topIdea['ai']['title'] }}
            </p>
            <p style="font-size:13.5px;color:var(--text2);line-height:1.75;margin:0">
              {{ $topIdea['ai']['description'] }}
            </p>
            @if($topIdea['ai']['general_objective'] ?? '')
              <p style="font-size:12.5px;font-weight:600;color:var(--text);margin:14px 0 8px">General objective</p>
              <p style="font-size:13px;color:var(--text2);line-height:1.7;margin:0">
                {{ $topIdea['ai']['general_objective'] }}
              </p>
            @endif
            @if(count($topIdea['ai']['specific_objectives'] ?? []) > 0)
              <p style="font-size:12.5px;font-weight:600;color:var(--text);margin:14px 0 8px">Specific objectives</p>
              <ul style="display:flex;flex-direction:column;gap:7px;margin:0;padding:0;list-style:none">
                @foreach($topIdea['ai']['specific_objectives'] as $objective)
                  <li style="display:flex;gap:8px;font-size:13px;color:var(--text2);line-height:1.6">
                    <span style="color:var(--amber);flex-shrink:0;margin-top:2px">▸</span>{{ $objective }}
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        </section>
      @endif

      {{-- Toggle button — one collapsible area for all DSS details --}}
      <button class="accordion-btn" style="padding:0;color:var(--amber);font-size:13px;margin-top:16px"
        @click="detailsOpen = !detailsOpen" :aria-expanded="detailsOpen" aria-controls="dss-details">
        <span x-text="detailsOpen ? 'Hide DSS details' : 'View DSS details'"></span>
        <svg class="accordion-icon" :class="detailsOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
      </button>
    </div>

    {{-- ── Expandable DSS details ── --}}
    <div id="dss-details"
         x-show="detailsOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         style="border-top:1px solid var(--border);padding:22px 28px 26px;">

      {{-- Why this idea? (DSS evidence) --}}
      <div class="co-section-head">
        <span class="co-section-dot"></span>
        <h3 class="co-section-title">Why did LIKHA identify this as a capstone opportunity?</h3>
      </div>

      <p style="font-size:13px;color:var(--text2);line-height:1.7;margin-bottom:16px">{{ $topIdea['explanation']['summary'] ?? '' }}</p>

      @if(($topIdea['severity_explanation'] ?? '') || ($topIdea['confidence_explanation'] ?? ''))
        <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:16px">
          @if($topIdea['severity_explanation'] ?? '')
            <p style="font-size:12px;color:var(--text3);line-height:1.6">⚠ {{ $topIdea['severity_explanation'] }}</p>
          @endif
          @if($topIdea['confidence_explanation'] ?? '')
            <p style="font-size:12px;color:var(--text3);line-height:1.6">✓ {{ $topIdea['confidence_explanation'] }}</p>
          @endif
        </div>
      @endif

      {{-- Evidence at a glance --}}
      <div class="co-evidence">
        <div class="co-evidence-item">
          <div class="co-evidence-value">{{ $topIdea['explanation']['factors']['reports'] ?? 'N/A' }}</div>
          <div class="co-evidence-label">Reports</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value">{{ $topIdea['explanation']['factors']['votes'] ?? 'N/A' }}</div>
          <div class="co-evidence-label">Support</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value">{{ $topIdea['explanation']['factors']['frequency_score'] ?? 'N/A' }}</div>
          <div class="co-evidence-label">Frequency</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value">{{ $topIdea['explanation']['factors']['impact_score'] ?? 'N/A' }}</div>
          <div class="co-evidence-label">Impact</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value" style="font-size:13px">{{ $topIdea['explanation']['factors']['top_affected_group'] ?? 'N/A' }}</div>
          <div class="co-evidence-label">Affected group</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value" style="font-size:13px">{{ $topIdea['severity_level'] ?? '—' }}</div>
          <div class="co-evidence-label">Severity</div>
        </div>
        <div class="co-evidence-item">
          <div class="co-evidence-value" style="font-size:13px">{{ $topIdea['confidence_level'] ?? '—' }}</div>
          <div class="co-evidence-label">Confidence</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:6px">
        @foreach(['impact','frequency','reports','votes'] as $r)
          @if($topIdea['explanation']['reasoning'][$r] ?? '')
            <p style="font-size:12px;color:var(--text3);padding-left:12px;border-left:2px solid var(--amid)">
              {{ $topIdea['explanation']['reasoning'][$r] }}
            </p>
          @endif
        @endforeach
      </div>

      {{-- Confidence breakdown --}}
      <div class="co-section-head" style="margin-top:26px">
        <span class="co-section-dot"></span>
        <h3 class="co-section-title">Confidence breakdown</h3>
      </div>

      <p style="font-size:12px;color:var(--text3);line-height:1.6;margin-bottom:18px">
        Confidence measures <strong style="color:var(--text2)">how trustworthy the data is</strong> — not how bad the problem is. High confidence means multiple independent sources agree.
      </p>

      <div style="display:flex;flex-direction:column;gap:18px">
        @foreach(['source_diversity'=>['Source diversity','How many unique people reported this?'],'frequency_consistency'=>['Frequency consistency','Do reporters agree on how often this happens?'],'sample_size'=>['Sample size','Is there enough data to generalize from?'],'community_validation'=>['Community validation','How many people upvoted beyond the reporters?']] as $k => [$label,$hint])
          <div>
            <div style="display:flex;justify-content:space-between;margin-bottom:3px">
              <span style="font-size:12.5px;font-weight:600;color:var(--text2)">{{ $label }}</span>
              <span style="font-size:12px;color:var(--text3)">{{ $topIdea['confidence_breakdown'][$k] ?? '—' }} / 5</span>
            </div>
            <div class="factor-bar">
              <div class="factor-fill" style="width:{{ min(100,(($topIdea['confidence_breakdown'][$k] ?? 0)/5)*100) }}%"></div>
            </div>
            <p style="font-size:11px;color:var(--text3);margin-top:4px">{{ $hint }}</p>
          </div>
        @endforeach
      </div>

      {{-- Objectives --}}
      <div class="co-section-head" style="margin-top:26px">
        <span class="co-section-dot"></span>
        <h3 class="co-section-title">Objectives</h3>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div>
          <p style="font-size:12.5px;font-weight:600;color:var(--text);margin-bottom:8px">General objective</p>
          <p style="font-size:13px;color:var(--text2);line-height:1.7">{{ $topIdea['general_objective'] ?? 'No objective available' }}</p>
        </div>
        <div>
          <p style="font-size:12.5px;font-weight:600;color:var(--text);margin-bottom:8px">Specific objectives</p>
          <ul style="display:flex;flex-direction:column;gap:7px;margin:0;padding:0;list-style:none">
            @foreach($topIdea['specific_objectives'] ?? [] as $obj)
              <li style="display:flex;gap:8px;font-size:13px;color:var(--text2);line-height:1.6">
                <span style="color:var(--amber);flex-shrink:0;margin-top:2px">▸</span>{{ $obj }}
              </li>
            @endforeach
          </ul>
        </div>
      </div>

    </div>{{-- /dss-details --}}

    {{-- Adviser review (separate evaluation layer) --}}
    @if(isset($topReview))
    <div class="co-adviser">
      <p class="co-adviser-kicker">
        <span class="co-adviser-dot"></span>
        Adviser Review — additional evaluation layer
      </p>
      <p style="font-size:13px;color:var(--text2);line-height:1.65;margin-bottom:8px">{{ $topReview->comment }}</p>
      @php
        $rr = $topReview->recommendation;
        $rrClass = $rr === 'Recommended' ? 'badge-green' : ($rr === 'Needs Revision' ? 'badge-amber' : 'badge-red');
      @endphp
      <span class="lk-badge {{ $rrClass }}">{{ $rr }}</span>
    </div>
    @endif

    @if($topIdea['office'] ?? null)
      @php
        $officeConfirmationState = $topIdea['office_confirmation_state'] ?? 'unavailable';
        $officeStatusLabel = match ($officeConfirmationState) {
          'available' => 'AVAILABLE',
          'office-confirmed' => 'OFFICE-CONFIRMED',
          'taken' => 'TAKEN',
          default => 'UNAVAILABLE',
        };
        $officeStatusClass = $officeConfirmationState === 'office-confirmed' ? 'badge-green' : ($officeConfirmationState === 'taken' ? 'badge-muted' : 'badge-amber');
      @endphp
      <section class="mx-7 mb-6 border-t pt-4" style="border-color:var(--border)" aria-label="Office opportunity details">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="space-y-1 text-sm" style="color:var(--text2)">
            <p>Office: <strong style="color:var(--text)">{{ $topIdea['office']->name }}</strong></p>
            <p>Representative: <strong style="color:var(--text)">{{ $topIdea['office']->representative?->name ?? 'Unavailable' }}</strong></p>
            <p>Contact:
              @if($topIdea['office']->contact_email)
                <a class="underline" href="mailto:{{ $topIdea['office']->contact_email }}">{{ $topIdea['office']->contact_email }}</a>
              @else
                <span>Not provided</span>
              @endif
            </p>
          </div>
          <span class="lk-badge {{ $officeStatusClass }}">{{ $officeStatusLabel }}</span>
        </div>
        @if($officeConfirmationState === 'available')
          <p class="mt-3 text-sm" style="color:var(--text3)">Consult the office representative before requesting confirmation. Consultation happens outside LIKHA; this request does not record a consultation or reserve the opportunity.</p>
          @auth
            @if($topIdea['has_pending_confirmation'] ?? false)
              <p class="mt-2 text-sm font-medium" style="color:var(--text2)">Your confirmation request is pending. The opportunity remains available until the office confirms.</p>
            @else
              <form method="POST" action="{{ route('office.confirmations.store', $topIdea['office_evaluation_id']) }}" class="mt-3">
                @csrf
                <button type="submit" class="btn-ghost text-sm">Request office confirmation</button>
              </form>
            @endif
          @endauth
        @elseif($officeConfirmationState === 'unavailable')
          <p class="mt-3 text-sm" style="color:var(--text3)">This opportunity is no longer available for office confirmation.</p>
        @endif
      </section>
    @endif

    {{-- Action buttons --}}
    <div style="padding:0 28px 28px;display:flex;flex-wrap:wrap;gap:10px;align-items:center">
      @auth
        <form method="POST" action="{{ route('idea.save') }}">
          @csrf
          <input type="hidden" name="title" value="{{ $topIdea['title'] ?? '' }}">
          <input type="hidden" name="description" value="{{ $topIdea['description'] ?? '' }}">
          <input type="hidden" name="category" value="{{ $category }}">
          <input type="hidden" name="office_id" value="{{ $topIdea['office_id'] ?? '' }}">
          <button type="submit" class="btn-amber">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Save Idea
          </button>
        </form>

        @if(config('services.ollama.enhance_enabled'))
          <form method="POST" action="{{ route('idea.enhance', $category) }}" onsubmit="var b=this.querySelector('button[type=submit]'); if(b){b.disabled=true;}">
            @csrf
            <input type="hidden" name="title" value="{{ $topIdea['title'] ?? '' }}">
            <input type="hidden" name="description" value="{{ $topIdea['description'] ?? '' }}">
            <input type="hidden" name="office_id" value="{{ $topIdea['office_id'] ?? '' }}">
            <input type="hidden" name="general_objective" value="{{ $topIdea['general_objective'] ?? '' }}">
            @foreach($topIdea['specific_objectives'] ?? [] as $objective)
              <input type="hidden" name="specific_objectives[]" value="{{ $objective }}">
            @endforeach
            <button type="submit" class="btn-ghost">✨ Improve with AI</button>
          </form>
        @endif

        @if(auth()->user()->role === 'adviser')
          <button type="button" onclick="document.getElementById('reviewForm').classList.toggle('hidden')" class="btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Add Review
          </button>
        @endif
      @else
        <button type="button" class="btn-amber" @click="loginOpen = true">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          Log in to save this idea
        </button>
      @endauth
    </div>

    {{-- Adviser review form --}}
    @auth
    @if(auth()->user()->role === 'adviser')
    <div id="reviewForm" class="hidden review-form">
      <p class="review-form-head">
        <span class="review-form-head-dot"></span>
        Adviser Evaluation Form
      </p>
      <p class="review-form-copy">
        Add your recommendation and scoring using the same 1 to 5 scale the system uses for evaluation.
      </p>
      <form method="POST" action="{{ route('adviser.review') }}" style="display:flex;flex-direction:column;gap:12px">
        @csrf
        <input type="hidden" name="idea_title" value="{{ $topIdea['title'] }}">
        <input type="hidden" name="category" value="{{ $category }}">
        <textarea id="review-comment" name="comment" rows="3" placeholder="Enter your feedback here…" class="lk-input" style="resize:vertical" aria-label="Adviser comment" required></textarea>
        <select id="review-recommendation" name="recommendation" class="lk-input" aria-label="Adviser recommendation" required>
          <option value="">Select Recommendation</option>
          <option value="Recommended">Recommended</option>
          <option value="Needs Revision">Needs Revision</option>
          <option value="Not Recommended">Not Recommended</option>
        </select>
        <div class="review-grid">
          @foreach(['feasibility'=>'Feasibility (1=Hard, 5=Easy)','impact'=>'Impact (1=Low, 5=High)','complexity'=>'Complexity (1=Hard, 5=Easy)','innovation'=>'Innovation (1=Low, 5=High)'] as $field => $label)
          <div>
            <label class="review-field-label" for="review-{{ $field }}">{{ $label }}</label>
            <input id="review-{{ $field }}" type="number" name="{{ $field }}" min="1" max="5" class="lk-input" required>
          </div>
          @endforeach
        </div>
        <div>
          <button type="submit" class="btn-amber">Submit Review</button>
        </div>
      </form>
    </div>
    @endif
    @endauth

  </div>
  @endif

  {{-- ══ SUPPORTING REPORTS ══ --}}
  {{-- ══ AI EXPLANATION OF THE TOP CLUSTER'S EVIDENCE ══
       Rendered only when a queued generation has already finished and passed
       the evidence validator. This page never waits on a model and never shows
       invented stand-in text, so when nothing is stored yet this section simply
       is not there and Supporting Evidence still reads on its own. --}}
  @if (! empty($clusterSynthesis))
  @php
    $activeSynthesisLanguage = $activeSynthesisLanguage ?? 'en';
    $clusterTranslation = $clusterTranslation ?? null;
    $translationPending = $translationPending ?? false;
    $displaySynthesis = ($activeSynthesisLanguage === 'fil' && ! empty($clusterTranslation)) ? $clusterTranslation : $clusterSynthesis;
    $synthToggleUrl = function (string $language) use ($category, $highlightedIdeaTitle) {
        $parameters = ['category' => $category, 'synth_lang' => $language];

        if (filled($highlightedIdeaTitle)) {
            $parameters['idea'] = $highlightedIdeaTitle;
        }

        return route('feedback.category', $parameters).($highlightedIdeaTitle ? '#idea-'.Str::slug($highlightedIdeaTitle) : '');
    };
  @endphp
  <section class="anim-2" aria-label="AI summary of the reports behind this opportunity">
    <div class="co-section-head" style="justify-content:space-between">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span class="co-section-dot"></span>
        <h2 class="co-section-title">What People Are Experiencing</h2>
        <span class="co-section-sub">Plain-language summary of the reports below</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
        <a href="{{ $synthToggleUrl('en') }}" class="lk-badge {{ $activeSynthesisLanguage === 'en' ? 'badge-amber' : 'badge-muted' }}" style="text-decoration:none">English</a>
        <a href="{{ $synthToggleUrl('fil') }}" class="lk-badge {{ $activeSynthesisLanguage === 'fil' ? 'badge-amber' : 'badge-muted' }}" style="text-decoration:none">Filipino</a>
        <span class="lk-badge badge-amber">AI-generated</span>
      </div>
    </div>

    <div class="co-synth-card">
      @if($activeSynthesisLanguage === 'fil' && empty($clusterTranslation))
        @if($translationPending)
          <p class="co-synth-summary">Filipino translation is being prepared…</p>
        @endif
        <p class="co-synth-summary">{{ $clusterSynthesis->summary }}</p>
      @else
        <p class="co-synth-summary">{{ $displaySynthesis->summary }}</p>
      @endif

      @if(count($displaySynthesis->patterns ?? []) > 0)
        <p class="co-synth-label">Recurring patterns</p>
        <ul class="co-synth-list">
          @foreach($displaySynthesis->patterns as $pattern)
            <li>
              <span class="co-synth-bullet" aria-hidden="true">&rsaquo;</span><span>{{ $pattern }}</span>
            </li>
          @endforeach
        </ul>
      @endif

      @if(count($displaySynthesis->experiences ?? []) > 0)
        <p class="co-synth-label">What reporters described</p>
        @foreach($displaySynthesis->experiences as $experience)
          <div class="co-synth-exp">
            <p class="co-synth-exp-title">{{ $experience['title'] ?? '' }}</p>
            <p class="co-synth-exp-body">{{ $experience['body'] ?? '' }}</p>
          </div>
        @endforeach
      @endif

      <p class="co-synth-note">The DSS decided this problem qualifies; AI only summarizes the evidence.</p>
    </div>
  </section>
  @endif

  <div class="anim-2">
    <div class="co-section-head" style="justify-content:space-between">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="co-section-dot"></span>
        <h2 class="co-section-title">Supporting Evidence</h2>
        <span class="co-section-sub">The real reports behind this opportunity</span>
      </div>
      <span class="lk-badge badge-muted">
        {{ $feedbacks->count() }} {{ Str::plural('report', $feedbacks->count()) }}
      </span>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px">
      @foreach($feedbacks as $feedback)
      <div class="report-row">
        <p style="font-size:13.5px;color:var(--text);line-height:1.7;margin-bottom:12px">
          {{ $feedback->description }}
        </p>
        @if($feedback->attachment_path)
          <div style="margin-bottom:12px">
            @if($feedback->attachment_type === 'image')
              <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" style="display:inline-block">
                <img src="{{ asset('storage/'.$feedback->attachment_path) }}" alt="Supporting attachment" style="max-width:180px;max-height:120px;border-radius:12px;border:1px solid var(--border);object-fit:cover">
              </a>
            @else
              <a href="{{ asset('storage/'.$feedback->attachment_path) }}" target="_blank" rel="noopener noreferrer" class="lk-badge badge-blue" style="text-decoration:none">
                <i data-lucide="paperclip" style="width:12px;height:12px;" aria-hidden="true"></i>
                View attachment
              </a>
            @endif
          </div>
        @endif
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:7px;font-size:11.5px">

          @if($feedback->frequency)
            <span class="lk-badge badge-muted">⏱ {{ $feedback->frequency }}</span>
          @endif

          @php
            $groups = is_array($feedback->affected_group)
              ? $feedback->affected_group
              : (json_decode($feedback->affected_group, true) ?? [$feedback->affected_group]);
            $groups = array_filter($groups ?? []);
          @endphp
          @if(count($groups))
            <span class="lk-badge badge-blue">👥 {{ implode(', ', $groups) }}</span>
          @endif

          @if($feedback->current_process)
            @php
              $noSol = str_contains(strtolower($feedback->current_process),'no solution')
                    || str_contains(strtolower($feedback->current_process),'no way');
            @endphp
            <span class="lk-badge {{ $noSol ? 'badge-red' : 'badge-green' }}">
              {{ $noSol ? '✕' : '⚙' }} {{ $feedback->current_process }}
            </span>
          @endif

          <span style="margin-left:auto;display:flex;align-items:center;gap:12px;color:var(--text3)">
            <span>{{ $feedback->created_at->diffForHumans() }}</span>
            @if(($feedback->votes_count ?? 0) > 0)
              <span style="font-weight:600;color:var(--amber)">▲ {{ $feedback->votes_count }}</span>
            @endif
          </span>
        </div>
      </div>
      @endforeach
    </div>
  </div>



  {{-- ══ IDEA COMPARISON ══ --}}
  @if(isset($topIdea) && count($otherIdeas) > 0)
  <div class="lk-card anim-2" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
      <div style="width:8px;height:8px;border-radius:50%;background:var(--amber)"></div>
      <h3 style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:var(--text)">Generated DSS Idea Comparison</h3>
    </div>
    <div style="overflow-x:auto">
      <table class="cmp-table" style="width:100%">
        <thead>
          <tr>
            <th style="text-align:left">Criteria</th>
            <th style="text-align:center;color:var(--amber)">Top idea</th>
            <th style="text-align:center;color:var(--text3)">Other idea</th>
          </tr>
        </thead>
        <tbody>
          @foreach(['score'=>'Idea Score','feasibility'=>'Feasibility','impact'=>'Impact','complexity'=>'Complexity','severity'=>'Severity','confidence'=>'Confidence'] as $key => $label)
          <tr>
            <td style="font-weight:500;color:var(--text2)">{{ $label }}</td>
            <td style="text-align:center" class="cmp-winner">{{ $topIdea['comparison'][$key] ?? '—' }}</td>
            <td style="text-align:center;color:var(--text3)">{{ $otherIdeas[0]['comparison'][$key] ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif


  {{-- ══ OTHER IDEAS ══ --}}
  @if(isset($otherIdeas) && count($otherIdeas) > 0)
  <div class="anim-3">
    <div class="co-section-head">
      <span class="co-section-dot"></span>
      <h3 class="co-section-title">Other Generated DSS Ideas</h3>
      <span class="co-section-sub">Additional directions from the same problem area</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
      @foreach($otherIdeas as $idea)
      @php
        $ideaProjectName = $idea['project_name'] ?? null;
        $ideaConceptPrimary = $idea['concept']['primary'] ?? null;
        $ideaTitle = $idea['title'] ?? 'Capstone Opportunity';
        $ip = $idea['priority'] ?? 'Low';
        $pClass = $ip === 'High' ? 'badge-red' : ($ip === 'Medium' ? 'badge-amber' : 'badge-muted');
      @endphp
      <div
        id="idea-{{ \Illuminate\Support\Str::slug($ideaTitle) }}"
        class="lk-card {{ $highlightedIdeaTitle === $ideaTitle ? 'idea-highlight' : '' }}"
        style="padding:20px;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          <span class="lk-badge badge-amber">Generated DSS Idea</span>
          <span class="lk-badge {{ $pClass }}">
            {{ $ip }} Priority
          </span>
        </div>

        <div>
          <h4 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:var(--text);line-height:1.35;margin-bottom:4px">
            {{ $ideaProjectName ?: $ideaTitle }}
          </h4>
          @if($ideaConceptPrimary)
            <div style="font-size:12.5px;font-weight:600;color:var(--amber);line-height:1.4">
              {{ $ideaConceptPrimary }}
            </div>
          @endif
          @if($ideaProjectName && $ideaProjectName !== $ideaTitle)
            <div style="font-size:11px;color:var(--text3);margin-top:3px;line-height:1.4">
              DSS ID: {{ $ideaTitle }}
            </div>
          @endif
        </div>

        <p style="font-size:11.5px;color:var(--text3);margin-top:-4px">
          Problem cluster: <strong style="color:var(--text2)">{{ $idea['cluster_label'] ?? Str::headline($idea['group'] ?? '') }}</strong>
        </p>

        <p style="font-size:12.5px;color:var(--text2);line-height:1.65;flex:1">
          {{ Str::limit($idea['description'] ?? '', 140) }}
        </p>

        @if($idea['office'] ?? null)
          @php
            $officeConfirmationState = $idea['office_confirmation_state'] ?? 'unavailable';
            $officeStatusLabel = match ($officeConfirmationState) {
              'available' => 'AVAILABLE',
              'office-confirmed' => 'OFFICE-CONFIRMED',
              'taken' => 'TAKEN',
              default => 'UNAVAILABLE',
            };
            $officeStatusClass = $officeConfirmationState === 'office-confirmed' ? 'badge-green' : ($officeConfirmationState === 'taken' ? 'badge-muted' : 'badge-amber');
          @endphp
          <section class="border-t pt-3" style="border-color:var(--border)" aria-label="Office opportunity details">
            <p style="font-size:11.5px;color:var(--text2)">Office: <strong>{{ $idea['office']->name }}</strong></p>
            <p style="font-size:11.5px;color:var(--text2)">Representative: <strong>{{ $idea['office']->representative?->name ?? 'Unavailable' }}</strong></p>
            <p style="font-size:11.5px;color:var(--text2)">Contact:
              @if($idea['office']->contact_email)
                <a class="underline" href="mailto:{{ $idea['office']->contact_email }}">{{ $idea['office']->contact_email }}</a>
              @else
                Not provided
              @endif
            </p>
            <span class="lk-badge {{ $officeStatusClass }} mt-2">{{ $officeStatusLabel }}</span>
            @if($officeConfirmationState === 'available')
              <p class="mt-2" style="font-size:11.5px;color:var(--text3)">Consult the office representative before requesting confirmation. Consultation happens outside LIKHA.</p>
              @auth
                @if($idea['has_pending_confirmation'] ?? false)
                  <p class="mt-2" style="font-size:11.5px;color:var(--text2)">Your request is pending; this opportunity remains available.</p>
                @else
                  <form method="POST" action="{{ route('office.confirmations.store', $idea['office_evaluation_id']) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn-ghost" style="width:100%;justify-content:center;font-size:12px;padding:8px">Request office confirmation</button>
                  </form>
                @endif
              @endauth
            @elseif($officeConfirmationState === 'unavailable')
              <p class="mt-2" style="font-size:11.5px;color:var(--text3)">No longer available for confirmation.</p>
            @endif
          </section>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:10px;font-size:11.5px;color:var(--text3);padding-top:10px;border-top:1px solid var(--border)">
          <span>Score: <strong style="color:var(--text2)">{{ $idea['score'] ?? 0 }}</strong></span>
          <span>Severity: <strong style="color:var(--amber)">{{ $idea['severity_level'] ?? '—' }}</strong></span>
          <span>Conf: <strong style="color:var(--blue)">{{ $idea['confidence_level'] ?? '—' }}</strong></span>
        </div>

        <div style="margin-top:4px">
          @auth
            <form method="POST" action="{{ route('idea.save') }}">
              @csrf
              <input type="hidden" name="title" value="{{ $ideaTitle }}">
              <input type="hidden" name="description" value="{{ $idea['description'] ?? '' }}">
              <input type="hidden" name="category" value="{{ $category }}">
              <input type="hidden" name="office_id" value="{{ $idea['office_id'] ?? '' }}">
              <button type="submit" class="btn-amber" style="width:100%;justify-content:center;font-size:12.5px;padding:8px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                Save Idea
              </button>
            </form>
          @else
            <button type="button" class="btn-ghost" @click="loginOpen = true" style="width:100%;justify-content:center;font-size:12px;padding:8px">
              Log in to save this idea
            </button>
          @endauth
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif


  {{-- ══ STUDENT NEXT STEPS ══ --}}
  <div class="anim-4">
    <div class="co-section-head">
      <span class="co-section-dot"></span>
      <h2 class="co-section-title">Your Next Steps</h2>
      <span class="co-section-sub">A capstone opportunity is a starting point, not a finished project</span>
    </div>
    <div class="co-steps">
      <div class="co-step">
        <div class="co-step-num">01 — EXPLORE</div>
        <div class="co-step-title">Study the problem</div>
        <p class="co-step-copy">Review the institutional problem and the supporting evidence behind this opportunity.</p>
      </div>
      <div class="co-step">
        <div class="co-step-num">02 — CONSULT</div>
        <div class="co-step-title">Talk to the office & adviser</div>
        <p class="co-step-copy">Discuss the problem with the concerned office and your adviser before committing.</p>
      </div>
      <div class="co-step">
        <div class="co-step-num">03 — REFINE</div>
        <div class="co-step-title">Shape the final capstone</div>
        <p class="co-step-copy">Adapt the opportunity into the actual capstone proposal that fits your team and scope.</p>
      </div>
    </div>
  </div>

</div>
@endsection
