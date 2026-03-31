@extends('layouts.app')

@section('title', 'Submit a Campus Problem')
@section('subtitle', 'Report an issue that affects members of the campus community')

@section('content')

<style>
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.25);
  }

  @keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .fade-up { animation: fadeInUp .5s ease both; }

  /* ── Shared input/select/textarea ── */
  .field {
    width: 100%; border: 1px solid #d1d5db; border-radius: 12px;
    padding: 10px 14px; font-size: .8125rem; outline: none;
    background: transparent; transition: border-color .2s, box-shadow .2s; color: inherit;
  }
  .field:hover  { border-color: rgba(251,176,52,.5); }
  .field:focus  { border-color: var(--amber); box-shadow: 0 0 0 3px var(--amber-dim); }
  .dark .field  { border-color: #374151; }

  /* ── Floating label ── */
  .float-wrap { position: relative; }
  .float-wrap input { padding-top: 20px; padding-bottom: 8px; }
  .float-label {
    position: absolute; left: 14px; top: 11px;
    font-size: .8125rem; color: #9ca3af; pointer-events: none;
    transition: top .15s, font-size .15s, color .15s;
  }
  .float-wrap input:not(:placeholder-shown) ~ .float-label,
  .float-wrap input:focus ~ .float-label {
    top: 5px; font-size: .68rem; color: var(--amber);
  }

  /* ── Checkbox card ── */
  .check-card {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 14px; border-radius: 12px;
    border: 1px solid #e5e7eb; cursor: pointer;
    transition: all .15s; user-select: none;
  }
  .dark .check-card { border-color: #374151; }
  .check-card:hover { border-color: rgba(251,176,52,.5); background: var(--amber-dim); }
  .check-card input[type=checkbox] {
    width: 16px; height: 16px; border-radius: 5px; flex-shrink: 0;
    accent-color: var(--amber); cursor: pointer;
  }
  .check-card.is-checked {
    border-color: var(--amber-border);
    background: var(--amber-dim);
  }

  /* ── Step dots ── */
  .step-dot {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 700;
    border: 2px solid #e5e7eb; color: #9ca3af; background: white;
    transition: all .3s; z-index: 1;
  }
  .dark .step-dot { background: #1e293b; border-color: #374151; }
  .step-dot.done  { background: var(--amber); border-color: var(--amber); color: #0e0f14; }
  .step-dot.active { border-color: var(--amber); color: var(--amber); box-shadow: 0 0 0 3px var(--amber-dim); }

  .progress-track { position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: #e5e7eb; transform: translateY(-50%); z-index: 0; }
  .dark .progress-track { background: #374151; }
  .progress-fill { height: 100%; background: var(--amber); transition: width .4s ease; }

  /* ── Buttons ── */
  .btn-back {
    padding: 9px 20px; border-radius: 10px; font-size: .8125rem; font-weight: 500;
    background: transparent; border: 1px solid #d1d5db; color: #6b7280; cursor: pointer; transition: all .18s;
  }
  .btn-back:hover { border-color: var(--amber-border); color: #374151; }
  .dark .btn-back { border-color: #374151; color: #9ca3af; }
  .btn-next {
    padding: 9px 24px; border-radius: 10px; font-size: .8125rem; font-weight: 600;
    background: var(--amber); color: #0e0f14; cursor: pointer; border: none;
    box-shadow: 0 4px 14px rgba(251,176,52,.3); transition: all .18s;
  }
  .btn-next:hover { background: #fcc050; box-shadow: 0 6px 20px rgba(251,176,52,.4); transform: translateY(-1px); }
  .btn-submit {
    padding: 9px 24px; border-radius: 10px; font-size: .8125rem; font-weight: 600;
    background: #16a34a; color: white; cursor: pointer; border: none;
    box-shadow: 0 4px 14px rgba(22,163,74,.25); transition: all .18s;
  }
  .btn-submit:hover { background: #15803d; transform: translateY(-1px); }

  /* ── Similar results ── */
  .similar-card { border-radius: 12px; border: 1px solid var(--amber-border); background: rgba(251,176,52,.05); padding: 14px; animation: fadeInUp .3s ease both; }
  .similar-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; margin-top: 8px; transition: border-color .2s, box-shadow .2s; }
  .dark .similar-item { border-color: #374151; }
  .similar-item:hover { border-color: var(--amber-border); box-shadow: 0 4px 12px rgba(0,0,0,.08); }

  /* ── Section header ── */
  .section-head { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
  .section-head-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--amber); animation: pulse-dot 2s ease-in-out infinite; flex-shrink: 0; }
  @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.75)} }
  .section-head-title { font-size: .9375rem; font-weight: 600; font-family: 'Sora', sans-serif; }
  .section-head-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(251,176,52,.3), transparent); }

  /* ── Field label ── */
  .field-label { display: block; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: #9ca3af; margin-bottom: 8px; }
</style>

<div class="max-w-2xl mx-auto px-4 sm:px-6 fade-up space-y-5">

  {{-- ── Similar Problems Warning ── --}}
  @if(session('similarProblems'))
  <div class="rounded-2xl border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-5">
    <p class="font-semibold text-yellow-800 dark:text-yellow-300 text-sm mb-2 flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
      Similar problems already reported
    </p>
    <ul class="space-y-1 text-sm text-yellow-700 dark:text-yellow-300 mb-2">
      @foreach(session('similarProblems') as $problem)
        <li class="flex gap-2"><span class="opacity-60">▸</span>{{ $problem->title }}</li>
      @endforeach
    </ul>
    <p class="text-xs text-yellow-600 dark:text-yellow-400">You can support an existing problem instead of submitting a new one.</p>
  </div>
  @endif

  {{-- ── Main Form Card ── --}}
  <div x-data="{
      step: 1,
      showError: false,
      otherGroup: false,
      otherGroupVal: '',
      otherCategory: false,
      otherProcess: false,

      canProceed() {
          if (this.step === 1) {
              const title    = document.querySelector('[name=title]').value.trim() !== '';
              const checked  = document.querySelectorAll('[name=\'affected_group[]\']:checked').length > 0;
              const category = document.querySelector('[name=category]').value !== '';
              return title && checked && category;
          }
          if (this.step === 2) {
              return document.querySelector('[name=description]').value.trim() !== '' &&
                     document.querySelector('[name=impact]').value.trim() !== '';
          }
          if (this.step === 3) {
              return document.querySelector('[name=frequency]').value !== '' &&
                     document.querySelector('[name=affected_users]').value !== '' &&
                     document.querySelector('[name=current_process]').value !== '';
          }
          return true;
      }
  }"
  class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">

    <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500"></div>

    <form method="POST" action="{{ route('feedback.store') }}" class="p-6 sm:p-8 space-y-8">
      @csrf
      <input type="hidden" name="force_submit" value="1">

      {{-- ── Step Indicator ── --}}
      <div>
        <div class="relative flex items-center justify-between mb-3">
          <div class="progress-track">
            <div class="progress-fill" :style="'width:' + ((step-1)/3*100) + '%'"></div>
          </div>
          @foreach([1 => 'Info', 2 => 'Details', 3 => 'Context', 4 => 'Review'] as $n => $label)
          <div class="flex flex-col items-center gap-1.5">
            <div class="step-dot" :class="step > {{ $n }} ? 'done' : (step === {{ $n }} ? 'active' : '')">
              <template x-if="step > {{ $n }}">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <path d="M5 13l4 4L19 7"/>
                </svg>
              </template>
              <template x-if="step <= {{ $n }}">
                <span>{{ $n }}</span>
              </template>
            </div>
            <span class="text-[10px] font-medium hidden sm:block"
                  :class="step === {{ $n }} ? 'text-amber-500' : 'text-gray-400'">{{ $label }}</span>
          </div>
          @endforeach
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 text-right mt-1">
          Step <span x-text="step"></span> of 4
        </p>
      </div>

      {{-- ══════════════════════════════════════
           STEP 1 — Basic Information
      ══════════════════════════════════════ --}}
      <div x-show="step === 1" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title text-gray-800 dark:text-white">Basic Information</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-6">

          {{-- Problem Title --}}
          <div class="float-wrap">
            <input type="text" name="title" id="problemTitle"
              value="{{ old('title') }}" placeholder=" "
              class="field" :required="step === 1"
              @input="showError = false"/>
            <label class="float-label">Problem Title</label>
            <div id="similarResults" class="mt-3"></div>
          </div>

          {{-- Who is affected — checkboxes ── --}}
          <div>
            <label class="field-label">
              Who is affected?
              <span class="text-red-400 normal-case font-normal ml-0.5">*</span>
            </label>

            <div class="grid grid-cols-2 gap-2">
              @foreach(['Students', 'Faculty', 'Staff', 'Administration'] as $group)
              <label class="check-card">
                <input type="checkbox" name="affected_group[]" value="{{ $group }}"
                  {{ is_array(old('affected_group')) && in_array($group, old('affected_group')) ? 'checked' : '' }}/>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $group }}</span>
              </label>
              @endforeach

              {{-- Other — full width --}}
              <label class="check-card col-span-2" :class="otherGroup ? 'is-checked' : ''">
                <input type="checkbox" x-model="otherGroup"/>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Other</span>
              </label>
            </div>

            {{-- Other text input --}}
            <div x-show="otherGroup" x-transition.opacity class="mt-2 space-y-1">
              <input type="text" name="affected_group_other"
                     x-model="otherGroupVal"
                     placeholder="e.g. Campus visitors, Alumni, Guests…"
                     class="field"/>
              <input type="hidden" name="affected_group[]"
                     :value="otherGroupVal.trim() ? 'Other: ' + otherGroupVal.trim() : ''"/>
              <p class="text-[11px] text-gray-400">Specify who else is affected.</p>
            </div>

            <p class="text-[11px] text-gray-400 mt-2">Select all that apply.</p>
          </div>

          {{-- Affected Area — single select + Other --}}
          <div>
            <label class="field-label">
              Affected Area
              <span class="text-red-400 normal-case font-normal ml-0.5">*</span>
            </label>

            <select name="category" class="field" :required="step === 1"
                    @change="otherCategory = $event.target.value === 'Other'">
              <option value="">Select area…</option>
              <option value="Enrollment"       {{ old('category') === 'Enrollment'       ? 'selected' : '' }}>Enrollment</option>
              <option value="Academic Process" {{ old('category') === 'Academic Process' ? 'selected' : '' }}>Academic Process</option>
              <option value="Facilities"       {{ old('category') === 'Facilities'       ? 'selected' : '' }}>Facilities</option>
              <option value="Library"          {{ old('category') === 'Library'          ? 'selected' : '' }}>Library</option>
              <option value="Scheduling"       {{ old('category') === 'Scheduling'       ? 'selected' : '' }}>Scheduling</option>
              <option value="Other"            {{ old('category') === 'Other'            ? 'selected' : '' }}>Other (please specify)</option>
            </select>

            <div x-show="otherCategory" x-transition.opacity class="mt-2 space-y-1">
              <input type="text" name="category_other"
                     value="{{ old('category_other') }}"
                     placeholder="Describe the affected area…"
                     class="field"/>
              <p class="text-[11px] text-gray-400">
                This will be reviewed by admin and may become a new category.
              </p>
            </div>
          </div>

        </div>
      </div>

      {{-- ══════════════════════════════════════
           STEP 2 — Problem Details
      ══════════════════════════════════════ --}}
      <div x-show="step === 2" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title text-gray-800 dark:text-white">Problem Details</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-5">
          <div>
            <label class="field-label">Detailed Description</label>
            <textarea name="description" rows="4" class="field resize-none"
              placeholder="Describe where and when this problem happens…"
              :required="step === 2">{{ old('description') }}</textarea>
          </div>
          <div>
            <label class="field-label">Impact</label>
            <textarea name="impact" rows="3" class="field resize-none"
              placeholder="How does this affect people?"
              :required="step === 2">{{ old('impact') }}</textarea>
          </div>
        </div>
      </div>

      {{-- ══════════════════════════════════════
           STEP 3 — Context
      ══════════════════════════════════════ --}}
      <div x-show="step === 3" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title text-gray-800 dark:text-white">Context</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">

            <div>
              <label class="field-label">Frequency</label>
              <select name="frequency" class="field" :required="step === 3">
                <option value="">Select…</option>
                <option value="Rarely"    {{ old('frequency') === 'Rarely'    ? 'selected' : '' }}>Rarely</option>
                <option value="Sometimes" {{ old('frequency') === 'Sometimes' ? 'selected' : '' }}>Sometimes</option>
                <option value="Often"     {{ old('frequency') === 'Often'     ? 'selected' : '' }}>Often</option>
                <option value="Everyday"  {{ old('frequency') === 'Everyday'  ? 'selected' : '' }}>Everyday</option>
              </select>
            </div>

            <div>
              <label class="field-label">People Affected</label>
              <select name="affected_users" class="field" :required="step === 3">
                <option value="">Select…</option>
                <option value="Less than 50"  {{ old('affected_users') === 'Less than 50'  ? 'selected' : '' }}>Less than 50</option>
                <option value="50-200"        {{ old('affected_users') === '50-200'        ? 'selected' : '' }}>50 – 200</option>
                <option value="200-500"       {{ old('affected_users') === '200-500'       ? 'selected' : '' }}>200 – 500</option>
                <option value="More than 500" {{ old('affected_users') === 'More than 500' ? 'selected' : '' }}>More than 500</option>
              </select>
            </div>

          </div>

          {{-- Current Handling + Other --}}
          <div>
  <label class="field-label">
    Is there an existing solution for this problem?
  </label>
  <p class="text-xs text-gray-400 dark:text-gray-500 mb-3 -mt-1">
    Tell us how this is currently being dealt with — or if it isn't at all.
  </p>
 
  {{-- Option cards — radio style so only one can be selected ── --}}
  <div class="space-y-2">
 
    @php
    $processOptions = [
        'No solution exists at all'           => ['icon' => 'x-circle',       'sub' => 'There is no way to report or resolve this problem.'],
        'Manual or paper-based process'       => ['icon' => 'file-text',      'sub' => 'It involves physical forms, logbooks, or in-person steps.'],
        'Broken or unreliable online system'  => ['icon' => 'wifi-off',       'sub' => 'An online system exists but it doesn\'t work properly.'],
        'Report verbally to staff'            => ['icon' => 'message-circle', 'sub' => 'People tell staff or faculty directly, with no formal tracking.'],
        'Just wait and hope it gets fixed'    => ['icon' => 'clock',          'sub' => 'There\'s nothing to do but wait — no clear process.'],
        'Send an email or message'            => ['icon' => 'mail',           'sub' => 'Issues are reported via email, chat, or messaging apps.'],
    ];
    @endphp
 
    @foreach($processOptions as $value => $meta)
    <label class="check-card group" style="align-items:flex-start; gap:12px;">
      <input type="radio" name="current_process" value="{{ $value }}"
             {{ old('current_process') === $value ? 'checked' : '' }}
             class="mt-0.5 shrink-0" style="accent-color:var(--amber); width:15px; height:15px;"/>
      <div class="flex items-start gap-3 flex-1 min-w-0">
        <i data-lucide="{{ $meta['icon'] }}"
           class="w-4 h-4 shrink-0 mt-0.5 text-gray-400 group-hover:text-amber-400 transition"
           style="color:var(--amber); opacity:.7;"></i>
        <div>
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $value }}</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $meta['sub'] }}</p>
        </div>
      </div>
    </label>
    @endforeach
 
    {{-- Other ── --}}
    <label class="check-card group" style="align-items:flex-start; gap:12px;"
           :class="otherProcess ? 'is-checked' : ''"
           @change="otherProcess = $el.querySelector('input[type=radio]').checked">
      <input type="radio" name="current_process" value="Other"
             {{ old('current_process') === 'Other' ? 'checked' : '' }}
             x-model="currentProcessVal"
             class="mt-0.5 shrink-0" style="accent-color:var(--amber); width:15px; height:15px;"
             @change="otherProcess = true"/>
      <div class="flex items-start gap-3 flex-1 min-w-0">
        <i data-lucide="edit-3" class="w-4 h-4 shrink-0 mt-0.5" style="color:var(--amber); opacity:.7;"></i>
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Other</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Describe how it's currently being handled.</p>
        </div>
      </div>
    </label>
 
  </div>
 
  {{-- Other text input ── --}}
  <div x-show="otherProcess" x-transition.opacity class="mt-3">
    <input type="text" name="current_process_other"
           value="{{ old('current_process_other') }}"
           placeholder="e.g. The department head handles it case by case…"
           class="field"/>
  </div>
</div>
        </div>
      </div>

      {{-- ══════════════════════════════════════
           STEP 4 — Review & Submit
      ══════════════════════════════════════ --}}
      <div x-show="step === 4" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title text-gray-800 dark:text-white">Review & Submit</span>
          <span class="section-head-line"></span>
        </div>

        <div class="rounded-xl border border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/40 p-5 space-y-3 mb-5">
          <p class="text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1">Summary</p>
          <div class="flex gap-3 text-sm">
            <span class="font-semibold text-gray-700 dark:text-gray-300 w-24 shrink-0">Title</span>
            <span id="review-title" class="text-gray-500 dark:text-gray-400">—</span>
          </div>
          <div class="flex gap-3 text-sm">
            <span class="font-semibold text-gray-700 dark:text-gray-300 w-24 shrink-0">Group(s)</span>
            <span id="review-group" class="text-gray-500 dark:text-gray-400">—</span>
          </div>
          <div class="flex gap-3 text-sm">
            <span class="font-semibold text-gray-700 dark:text-gray-300 w-24 shrink-0">Area</span>
            <span id="review-category" class="text-gray-500 dark:text-gray-400">—</span>
          </div>
          <div class="flex gap-3 text-sm">
            <span class="font-semibold text-gray-700 dark:text-gray-300 w-24 shrink-0">Frequency</span>
            <span id="review-freq" class="text-gray-500 dark:text-gray-400">—</span>
          </div>
        </div>

        <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
          <input type="checkbox" name="is_anonymous"
                 class="rounded border-gray-300 text-amber-500 focus:ring-amber-400 w-4 h-4"/>
          Submit anonymously
        </label>
      </div>

      {{-- ── Navigation ── --}}
      <div>
        <p x-show="showError" class="text-xs text-red-500 mb-3 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
          </svg>
          Please fill in all required fields before proceeding.
        </p>

        <div class="flex items-center justify-between gap-3">

          <button type="button" class="btn-back"
            @click="step = Math.max(step - 1, 1); showError = false"
            x-show="step > 1">
            ← Back
          </button>

          <button type="button" class="btn-next ml-auto"
            x-show="step < 4"
            @click="canProceed()
              ? (step = Math.min(step + 1, 4), showError = false, updateReview())
              : showError = true">
            Next →
          </button>

          <button type="submit" class="btn-submit ml-auto" x-show="step === 4">
            Submit Problem
          </button>

        </div>
      </div>

    </form>
  </div>

</div>

{{-- ── Scripts ── --}}
<script>
// ── Live Similar Search ──
const titleInput = document.getElementById('problemTitle');
const resultsBox = document.getElementById('similarResults');
let timeout = null;

if (titleInput) {
  titleInput.addEventListener('keyup', function () {
    clearTimeout(timeout);
    const value = this.value;
    if (value.length < 5) { resultsBox.innerHTML = ''; return; }

    timeout = setTimeout(() => {
      resultsBox.innerHTML = `<p class="text-xs text-gray-400 animate-pulse">Checking for similar problems…</p>`;

      fetch(`/similar-problems?title=${encodeURIComponent(value)}`)
        .then(r => r.json())
        .then(data => {
          if (!data.length) { resultsBox.innerHTML = ''; return; }

          let html = `<div class="similar-card">
            <p class="text-xs font-semibold flex items-center gap-1.5 mb-2" style="color:var(--amber);">
              <span style="width:6px;height:6px;border-radius:50%;background:var(--amber);display:inline-block;"></span>
              Similar problems found
            </p>`;

          data.forEach(p => {
            html += `<div class="similar-item">
              <p class="text-sm font-medium text-gray-800 dark:text-white">${p.title}</p>
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>${p.category}</span>
                <span style="color:var(--amber);">▲ ${p.votes_count} votes</span>
              </div>
            </div>`;
          });

          html += `<p class="text-xs text-gray-400 mt-3">Tip: You can support an existing problem instead of creating a duplicate.</p></div>`;
          resultsBox.innerHTML = html;
        })
        .catch(() => {
          resultsBox.innerHTML = `<p class="text-xs text-red-400">Error loading suggestions.</p>`;
        });
    }, 400);
  });
}

// ── Step 4 Review Summary ──
function updateReview() {
  // Title
  const title = document.querySelector('[name=title]');
  document.getElementById('review-title').textContent = title?.value || '—';

  // Affected groups — collect all checked + other
  const checked = [...document.querySelectorAll('[name="affected_group[]"]:checked')]
    .map(el => el.value).filter(v => v !== '');
  const otherInput = document.querySelector('[name=affected_group_other]');
  if (otherInput?.value.trim()) checked.push('Other: ' + otherInput.value.trim());
  document.getElementById('review-group').textContent = checked.length ? checked.join(', ') : '—';

  // Category
  const cat = document.querySelector('[name=category]');
  const catOther = document.querySelector('[name=category_other]');
  let catVal = cat?.value || '';
  if (catVal === 'Other' && catOther?.value.trim()) catVal = catOther.value.trim();
  document.getElementById('review-category').textContent = catVal || '—';

  // Frequency
  const freq = document.querySelector('[name=frequency]');
  document.getElementById('review-freq').textContent = freq?.value || '—';
}

// ── Sync check-card highlight on load (handles old() repopulation) ──
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.check-card input[type=checkbox]').forEach(input => {
    if (input.checked) input.closest('.check-card').classList.add('is-checked');
    input.addEventListener('change', () => {
      input.closest('.check-card').classList.toggle('is-checked', input.checked);
    });
  });
});
</script>

@endsection