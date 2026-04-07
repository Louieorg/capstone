@extends('layouts.app')

@section('title', $category . ' Issues')
@section('subtitle', 'Recurring problems and suggested capstone solution')

@section('content')
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
</style>

<div class="max-w-4xl mx-auto" style="display:flex;flex-direction:column;gap:20px">

  {{-- ══ TOP RECOMMENDED IDEA ══ --}}
  @if(isset($topIdea))
  <div x-data="{ detailsOpen:false, explainOpen:false, objOpen:false, evalOpen:false, confOpen:false }"
       class="lk-card anim-1"
       style="border-color:var(--amid);overflow:hidden">

    {{-- Amber top line --}}
    <div style="height:3px;background:linear-gradient(to right,#fbb034,#f97316)"></div>

    <div style="padding:28px 28px 24px">

      {{-- Eyebrow --}}
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <div style="display:inline-flex;align-items:center;gap:7px;padding:5px 13px;border-radius:999px;background:var(--adim);border:1px solid var(--amid);font-size:10.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--amber)">
          <span style="width:6px;height:6px;border-radius:50%;background:var(--amber);display:inline-block"></span>
          Top Recommended Idea
        </div>
        <span style="font-size:11.5px;color:var(--text3);max-width:280px;line-height:1.5">
          Based on real reported problems and evaluation data.
        </span>
      </div>

      {{-- Title --}}
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(18px,2.5vw,24px);font-weight:800;line-height:1.15;color:var(--text);margin-bottom:16px">
        {{ $topIdea['title'] ?? 'No title available' }}
      </h2>

      {{-- Badges --}}
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
        @php
          $pri = $topIdea['priority'] ?? 'Low';
          $sev = $topIdea['severity_level'] ?? 'Low';
          $con = $topIdea['confidence_level'] ?? 'Low';
          $priBadge = $pri === 'High' ? 'badge-red' : ($pri === 'Medium' ? 'badge-amber' : 'badge-muted');
          $sevBadge = $sev === 'High' ? 'badge-red' : ($sev === 'Medium' ? 'badge-amber' : 'badge-muted');
          $conBadge = $con === 'High' ? 'badge-green' : ($con === 'Medium' ? 'badge-blue' : 'badge-muted');
        @endphp
        <span class="lk-badge {{ $priBadge }}">{{ $pri }} Priority</span>
        <span class="lk-badge {{ $sevBadge }}">⚠ {{ $sev }} Severity</span>
        <span class="lk-badge {{ $conBadge }}">✓ {{ $con }} Confidence</span>
      </div>

      {{-- Explanations --}}
      <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:16px">
        @if($topIdea['severity_explanation'] ?? '')
          <p style="font-size:12px;color:var(--text3);line-height:1.6">⚠ {{ $topIdea['severity_explanation'] }}</p>
        @endif
        @if($topIdea['confidence_explanation'] ?? '')
          <p style="font-size:12px;color:var(--text3);line-height:1.6">✓ {{ $topIdea['confidence_explanation'] }}</p>
        @endif
      </div>

      {{-- Description --}}
      <p style="font-size:13.5px;color:var(--text2);line-height:1.75;margin-bottom:12px">
        {{ $topIdea['description'] ?? 'No description available' }}
      </p>

      {{-- Impact --}}
      @if(isset($topIdea['impact_simulation']['message']))
        <p style="font-size:13px;font-weight:600;color:var(--green);margin-bottom:16px">
          {{ $topIdea['impact_simulation']['message'] }}
        </p>
      @endif

      {{-- Toggle button --}}
      <button class="accordion-btn" style="padding:0;color:var(--amber);font-size:13px;margin-top:4px"
        @click="detailsOpen = !detailsOpen" :aria-expanded="detailsOpen">
        <span x-text="detailsOpen ? 'Hide details' : 'View details'"></span>
        <svg class="accordion-icon" :class="detailsOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
      </button>
    </div>

    {{-- ── Expandable details ── --}}
    <div x-show="detailsOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         style="border-top:1px solid var(--border);display:flex;flex-direction:column;gap:1px;background:var(--border)">

      {{-- Why this idea --}}
      <div style="background:var(--surface)">
        <button class="accordion-btn" @click="explainOpen=!explainOpen" :aria-expanded="explainOpen">
          <span>Why this idea?</span>
          <svg class="accordion-icon" :class="explainOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="explainOpen" x-transition class="accordion-body">
          <p style="font-size:13px;color:var(--text2);line-height:1.7;margin-bottom:16px">{{ $topIdea['explanation']['summary'] ?? '' }}</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 32px;margin-bottom:16px">
            @foreach(['reports'=>'Reports','votes'=>'Votes','frequency_score'=>'Frequency','impact_score'=>'Impact','top_affected_group'=>'Affected group'] as $k => $l)
              <p style="font-size:12px;color:var(--text3)">
                <span style="font-weight:600;color:var(--text2)">{{ $l }}:</span>
                {{ $topIdea['explanation']['factors'][$k] ?? 'N/A' }}
              </p>
            @endforeach
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
        </div>
      </div>

      {{-- Confidence breakdown --}}
      <div style="background:var(--surface)">
        <button class="accordion-btn" @click="confOpen=!confOpen" :aria-expanded="confOpen">
          <span>Confidence breakdown</span>
          <svg class="accordion-icon" :class="confOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="confOpen" x-transition class="accordion-body">
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
        </div>
      </div>

      {{-- Objectives --}}
      <div style="background:var(--surface)">
        <button class="accordion-btn" @click="objOpen=!objOpen" :aria-expanded="objOpen">
          <span>Objectives</span>
          <svg class="accordion-icon" :class="objOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="objOpen" x-transition class="accordion-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <div>
              <p style="font-size:12.5px;font-weight:600;color:var(--text);margin-bottom:8px">General objective</p>
              <p style="font-size:13px;color:var(--text2);line-height:1.7">{{ $topIdea['general_objective'] ?? 'No objective available' }}</p>
            </div>
            <div>
              <p style="font-size:12.5px;font-weight:600;color:var(--text);margin-bottom:8px">Specific objectives</p>
              <ul style="display:flex;flex-direction:column;gap:7px">
                @foreach($topIdea['specific_objectives'] ?? [] as $obj)
                  <li style="display:flex;gap:8px;font-size:13px;color:var(--text2);line-height:1.6">
                    <span style="color:var(--amber);flex-shrink:0;margin-top:2px">▸</span>{{ $obj }}
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      </div>

      {{-- Evaluation --}}
      @if(isset($topIdea['evaluation']))
      <div style="background:var(--surface)">
        <button class="accordion-btn" @click="evalOpen=!evalOpen" :aria-expanded="evalOpen">
          <span>Evaluation</span>
          <svg class="accordion-icon" :class="evalOpen?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="evalOpen" x-transition class="accordion-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:16px">
            @foreach(['feasibility'=>'Feasibility','impact'=>'Impact','complexity'=>'Complexity','innovation'=>'Innovation'] as $key => $label)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
              <span style="font-size:13px;color:var(--text2)">{{ $label }}</span>
              <span class="stars" style="font-size:13px">
                {{ str_repeat('★',$topIdea['evaluation'][$key] ?? 0) }}{{ str_repeat('☆',5-($topIdea['evaluation'][$key] ?? 0)) }}
              </span>
            </div>
            @endforeach
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <span style="font-size:13.5px;font-weight:600;color:var(--text)">
              Overall: <span style="color:var(--amber)">{{ $topIdea['evaluation']['overall_score'] ?? '—' }}</span>
            </span>
            @php
              $rec = $topIdea['evaluation']['recommendation'] ?? '';
              $recClass = $rec === 'Highly Recommended' ? 'badge-green' : ($rec === 'Recommended' ? 'badge-blue' : 'badge-red');
            @endphp
            <span class="lk-badge {{ $recClass }}">{{ $rec ?: '—' }}</span>
          </div>
        </div>
      </div>
      @endif

    </div>{{-- /expandable --}}

    {{-- Adviser review --}}
    @if(isset($topReview))
    <div style="margin:0 28px 28px;padding:16px 20px;border-radius:12px;background:var(--blue-bg);border:1px solid var(--blue-b)">
      <p style="font-size:12px;font-weight:600;color:var(--blue);margin-bottom:6px;display:flex;align-items:center;gap:6px">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--blue);display:inline-block"></span>
        Adviser Review
      </p>
      <p style="font-size:13px;color:var(--text2);line-height:1.65;margin-bottom:8px">{{ $topReview->comment }}</p>
      @php
        $rr = $topReview->recommendation;
        $rrClass = $rr === 'Recommended' ? 'badge-green' : ($rr === 'Needs Revision' ? 'badge-amber' : 'badge-red');
      @endphp
      <span class="lk-badge {{ $rrClass }}">{{ $rr }}</span>
    </div>
    @endif

    {{-- Action buttons --}}
    <div style="padding:0 28px 28px;display:flex;flex-wrap:wrap;gap:10px">
      @auth
        <form method="POST" action="{{ route('idea.save') }}">
          @csrf
          <input type="hidden" name="title" value="{{ $topIdea['title'] ?? '' }}">
          <input type="hidden" name="description" value="{{ $topIdea['description'] ?? '' }}">
          <input type="hidden" name="category" value="{{ $category }}">
          <button type="submit" class="btn-amber">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Save Best Idea
          </button>
        </form>

        @if(auth()->user()->role === 'adviser')
          <button type="button" onclick="document.getElementById('reviewForm').classList.toggle('hidden')" class="btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Add Review
          </button>
        @endif
      @else
        <p style="font-size:12px;color:var(--text3);align-self:center">Login to save this idea.</p>
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
        <textarea name="comment" rows="3" placeholder="Enter your feedback here…" class="lk-input" style="resize:vertical" required></textarea>
        <select name="recommendation" class="lk-input" required>
          <option value="">Select Recommendation</option>
          <option value="Recommended">Recommended</option>
          <option value="Needs Revision">Needs Revision</option>
          <option value="Not Recommended">Not Recommended</option>
        </select>
        <div class="review-grid">
          @foreach(['feasibility'=>'Feasibility (1=Hard, 5=Easy)','impact'=>'Impact (1=Low, 5=High)','complexity'=>'Complexity (1=Hard, 5=Easy)','innovation'=>'Innovation (1=Low, 5=High)'] as $field => $label)
          <div>
            <label class="review-field-label">{{ $label }}</label>
            <input type="number" name="{{ $field }}" min="1" max="5" class="lk-input" required>
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


  {{-- ══ IDEA COMPARISON ══ --}}
  @if(isset($topIdea) && count($otherIdeas) > 0)
  <div class="lk-card anim-2" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
      <div style="width:8px;height:8px;border-radius:50%;background:var(--amber)"></div>
      <h3 style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:var(--text)">Idea Comparison</h3>
    </div>
    <div style="overflow-x:auto">
      <table class="cmp-table" style="width:100%">
        <thead>
          <tr>
            <th style="text-align:left">Criteria</th>
            <th style="text-align:center;color:var(--amber)">Top idea</th>
            <th style="text-align:center;color:var(--text3)">Alternative</th>
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
    <p class="sec-label">Other suggested ideas</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
      @foreach($otherIdeas as $idea)
      <div class="lk-card" style="padding:20px 20px 18px;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span class="lk-badge badge-amber">Alternative</span>
          @php $ip = $idea['priority'] ?? 'Low'; @endphp
          <span class="lk-badge {{ $ip==='High'?'badge-red':($ip==='Medium'?'badge-amber':'badge-muted') }}">
            {{ $ip }} Priority
          </span>
        </div>
        <h4 style="font-family:'Sora',sans-serif;font-size:13.5px;font-weight:700;color:var(--text);line-height:1.4">
          {{ $idea['title'] ?? 'No title' }}
        </h4>
        <p style="font-size:12.5px;color:var(--text2);line-height:1.65;flex:1">
          {{ Str::limit($idea['description'] ?? '', 120) }}
        </p>
        <div style="display:flex;gap:14px;font-size:12px;color:var(--text3)">
          <span>Score: <strong style="color:var(--text2)">{{ $idea['score'] ?? 0 }}</strong></span>
          <span>Severity: <strong style="color:var(--amber)">{{ $idea['severity_level'] ?? '—' }}</strong></span>
          <span>Conf: <strong style="color:var(--blue)">{{ $idea['confidence_level'] ?? '—' }}</strong></span>
        </div>
        @auth
        <form method="POST" action="{{ route('idea.save') }}">
          @csrf
          <input type="hidden" name="title" value="{{ $idea['title'] ?? '' }}">
          <input type="hidden" name="description" value="{{ $idea['description'] ?? '' }}">
          <input type="hidden" name="category" value="{{ $category }}">
          <button type="submit" class="btn-amber" style="width:100%;justify-content:center;font-size:12.5px;padding:8px">
            Save Idea
          </button>
        </form>
        @endauth
      </div>
      @endforeach
    </div>
  </div>
  @endif


  {{-- ══ SUPPORTING REPORTS ══ --}}
  <div class="anim-4">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <p class="sec-label" style="margin-bottom:0">Reports supporting this issue</p>
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

</div>
@endsection
