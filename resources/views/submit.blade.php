@extends('layouts.app')

@section('title', 'Submit a Campus Problem')
@section('subtitle', 'Report an issue that affects members of the campus community')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 fade-up space-y-5">

  {{-- ── Similar Problems Warning ── --}}
  @if(session('similarProblems'))
  <div class="rounded-2xl border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-5">
    <p class="font-semibold text-yellow-800 dark:text-yellow-300 text-sm mb-2 flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
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
      canProceed() {
          if (this.step === 1)
              return document.querySelector('[name=title]').value.trim() !== '' &&
                     document.querySelector('[name=affected_group]').value !== '' &&
                     document.querySelector('[name=category]').value !== '';
          if (this.step === 2)
              return document.querySelector('[name=description]').value.trim() !== '' &&
                     document.querySelector('[name=impact]').value.trim() !== '';
          if (this.step === 3)
              return document.querySelector('[name=frequency]').value !== '' &&
                     document.querySelector('[name=affected_users]').value !== '' &&
                     document.querySelector('[name=current_process]').value !== '';
          return true;
      }
  }"
  class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">

    {{-- Amber top accent --}}
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
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
              </template>
              <template x-if="step <= {{ $n }}">
                <span>{{ $n }}</span>
              </template>
            </div>
            <span class="text-[10px] font-medium hidden sm:block" :class="step === {{ $n }} ? 'text-amber-500' : 'text-gray-400'">{{ $label }}</span>
          </div>
          @endforeach
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 text-right mt-1">Step <span x-text="step"></span> of 4</p>
      </div>

      {{-- ══ STEP 1: Basic Info ══ --}}
      <div x-show="step === 1" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Basic Information</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-5">

          {{-- Floating label title --}}
          <div class="float-wrap">
            <input type="text" name="title" id="problemTitle"
              value="{{ old('title') }}" placeholder=" "
              class="field" :required="step === 1" @input="showError = false"/>
            <label class="float-label">Problem Title</label>
            <div id="similarResults" class="mt-3"></div>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Who is affected?</label>
            <select name="affected_group" class="field" :required="step === 1">
              <option value="">Select group…</option>
              <option value="Students">Students</option>
              <option value="Faculty">Faculty</option>
              <option value="Staff">Staff</option>
              <option value="Administration">Administration</option>
              <option value="Multiple">Multiple Groups</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Affected Area</label>
            <select name="category" class="field" :required="step === 1">
              <option value="">Select area…</option>
              <option value="Enrollment">Enrollment</option>
              <option value="Academic Process">Academic Process</option>
              <option value="Facilities">Facilities</option>
              <option value="Library">Library</option>
              <option value="Scheduling">Scheduling</option>
            </select>
          </div>

        </div>
      </div>

      {{-- ══ STEP 2: Problem Details ══ --}}
      <div x-show="step === 2" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Problem Details</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-5">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Detailed Description</label>
            <textarea name="description" rows="4" class="field resize-none"
              placeholder="Describe where and when this problem happens…"
              :required="step === 2">{{ old('description') }}</textarea>
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Impact</label>
            <textarea name="impact" rows="3" class="field resize-none"
              placeholder="How does this affect people?"
              :required="step === 2">{{ old('impact') }}</textarea>
          </div>
        </div>
      </div>

      {{-- ══ STEP 3: Context ══ --}}
      <div x-show="step === 3" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Context</span>
          <span class="section-head-line"></span>
        </div>

        <div class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Frequency</label>
              <select name="frequency" class="field" :required="step === 3">
                <option value="">Select…</option>
                <option value="Rarely">Rarely</option>
                <option value="Sometimes">Sometimes</option>
                <option value="Often">Often</option>
                <option value="Everyday">Everyday</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">People Affected</label>
              <select name="affected_users" class="field" :required="step === 3">
                <option value="">Select…</option>
                <option value="Less than 50">Less than 50</option>
                <option value="50-200">50 – 200</option>
                <option value="200-500">200 – 500</option>
                <option value="More than 500">More than 500</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Current Handling</label>
            <select name="current_process" class="field" :required="step === 3">
              <option value="">Select process…</option>
              <option value="Manual reporting">Manual reporting</option>
              <option value="Verbal complaints">Verbal complaints</option>
              <option value="Email reporting">Email reporting</option>
              <option value="No system in place">No system in place</option>
            </select>
          </div>
        </div>
      </div>

      {{-- ══ STEP 4: Review & Submit ══ --}}
      <div x-show="step === 4" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Review & Submit</span>
          <span class="section-head-line"></span>
        </div>

        <div class="rounded-xl border border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/40 p-5 text-sm text-gray-600 dark:text-gray-400 space-y-2 mb-5">
          <p class="text-xs uppercase tracking-wider font-semibold text-gray-400 mb-3">Summary</p>
          <p><span class="font-semibold text-gray-700 dark:text-gray-300">Title:</span> <span id="review-title">—</span></p>
          <p><span class="font-semibold text-gray-700 dark:text-gray-300">Group:</span> <span id="review-group">—</span></p>
          <p><span class="font-semibold text-gray-700 dark:text-gray-300">Area:</span> <span id="review-category">—</span></p>
          <p><span class="font-semibold text-gray-700 dark:text-gray-300">Frequency:</span> <span id="review-freq">—</span></p>
        </div>

        <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
          <input type="checkbox" name="is_anonymous" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400"/>
          Submit anonymously
        </label>
      </div>

      {{-- ── Navigation ── --}}
      <div>
        {{-- Validation error --}}
        <p x-show="showError" class="text-xs text-red-500 mb-3 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
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
            @click="canProceed() ? (step = Math.min(step + 1, 4), showError = false, updateReview()) : showError = true">
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

{{-- ── Live Similar Search ── --}}
<script>
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
            <p class="text-xs font-semibold flex items-center gap-1.5 mb-2 text-amber-var">
              <span class="amber-dot"></span>
              Similar problems found
            </p>`;

          data.forEach(p => {
            html += `<div class="similar-item">
              <p class="text-sm font-medium text-gray-800 dark:text-white">${p.title}</p>
              <div class="flex justify-between text-xs text-gray-400 mt-1">
                <span>${p.category}</span>
                <span class="text-amber-var">▲ ${p.votes_count} votes</span>
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

// Populate step-4 review summary
function updateReview() {
  const get = sel => { const el = document.querySelector(sel); return el ? el.value : '—'; };
  const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
  setText('review-title',    get('[name=title]'));
  setText('review-group',    get('[name=affected_group]'));
  setText('review-category', get('[name=category]'));
  setText('review-freq',     get('[name=frequency]'));
}
</script>

@endsection