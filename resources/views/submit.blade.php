@extends('layouts.app')

@section('title', 'Submit a Campus Problem')
@section('subtitle', 'Report an issue that affects members of the campus community')

@section('content')

<style>
/* ── Tokens ── */
:root {
  --amber:         #fbb034;
  --adim:          rgba(251,176,52,0.10);
  --amid:          rgba(251,176,52,0.22);
  --aborder-h:     rgba(251,176,52,0.30);
  --border:        rgba(0,0,0,0.08);
  --text:          #1a1d24;
  --muted:         #6b7280;
  --muted2:        #b0b8c1;
  --surface:       #ffffff;
  --surface-alt:   #f5f5f8;
}

/* Light mode overrides for amber (more readable on white) */
:root:not(.dark) {
  --amber:         #b57318;
  --adim:          rgba(186,117,23,0.08);
  --amid:          rgba(186,117,23,0.18);
  --aborder-h:     rgba(186,117,23,0.35);
}

/* Dark mode */
html.dark {
  --border:        rgba(255,255,255,0.06);
  --text:          #f0f0f5;
  --muted:         #7e8194;
  --muted2:        #3e4055;
  --surface:       #13141a;
  --surface-alt:   #1a1b23;
}

@keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
.fade-up { animation: fadeInUp .5s ease both; }

/* ── Shared input/select/textarea ── */
.field {
  width: 100%; border: 1px solid var(--border); border-radius: 12px;
  padding: 10px 14px; font-size: .8125rem; outline: none;
  background: transparent; transition: border-color .2s, box-shadow .2s; color: inherit;
}
.field:hover  { border-color: var(--amid); }
.field:focus  { border-color: var(--amber); box-shadow: 0 0 0 3px var(--adim); }
select.field {
  color: var(--text);
  background-color: var(--surface);
  color-scheme: light dark;
}
select.field option {
  color: var(--text);
  background-color: var(--surface);
}
select.field:invalid {
  color: var(--muted);
}

/* ── Floating label ── */
.float-wrap { position: relative; }
.float-wrap input { padding-top: 20px; padding-bottom: 8px; }
.float-label {
  position: absolute; left: 14px; top: 11px;
  font-size: .8125rem; color: var(--muted2); pointer-events: none;
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
  border: 1px solid var(--border); cursor: pointer;
  transition: all .15s; user-select: none;
}
.check-card:hover { border-color: var(--amid); background: var(--adim); }
.check-card input[type=checkbox] {
  width: 16px; height: 16px; border-radius: 5px; flex-shrink: 0;
  accent-color: var(--amber); cursor: pointer;
}
.check-card.is-checked {
  border-color: var(--amid);
  background: var(--adim);
}

/* ── Step dots ── */
.step-dot {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .7rem; font-weight: 700;
  border: 2px solid var(--border); color: var(--muted2); background: var(--surface);
  transition: all .3s; z-index: 1;
}
.step-dot.done  { background: var(--amber); border-color: var(--amber); color: #0a0b0f; }
.step-dot.active { border-color: var(--amber); color: var(--amber); box-shadow: 0 0 0 3px var(--adim); }

.progress-track { position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: var(--border); transform: translateY(-50%); z-index: 0; }
.progress-fill { height: 100%; background: var(--amber); transition: width .4s ease; }

/* ── Buttons ── */
.btn-back {
  padding: 9px 20px; border-radius: 10px; font-size: .8125rem; font-weight: 500;
  background: transparent; border: 1px solid var(--border); color: var(--muted); cursor: pointer; transition: all .18s;
}
.btn-back:hover { border-color: var(--amid); color: var(--text); }
.btn-next {
  padding: 9px 24px; border-radius: 10px; font-size: .8125rem; font-weight: 600;
  background: var(--amber); color: #0a0b0f; cursor: pointer; border: none;
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
.similar-card { border-radius: 12px; border: 1px solid var(--amid); background: var(--adim); padding: 14px; animation: fadeInUp .3s ease both; }
.similar-item { border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; margin-top: 8px; transition: border-color .2s, box-shadow .2s; }
.similar-item:hover { border-color: var(--amid); box-shadow: 0 4px 12px rgba(0,0,0,.08); }

/* ── Section header ── */
.section-head { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
.section-head-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--amber); animation: pulse-dot 2s ease-in-out infinite; flex-shrink: 0; }
@keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.75)} }
.section-head-title { font-size: .9375rem; font-weight: 600; font-family: 'Sora', sans-serif; color: var(--text); }
.section-head-line { flex: 1; height: 1px; background: linear-gradient(to right, var(--amid), transparent); }

/* ── Field label ── */
.field-label { display: block; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--muted2); margin-bottom: 8px; }
</style>

<div class="max-w-2xl mx-auto px-4 sm:px-6 fade-up space-y-5">

@if ($errors->any())
<div style="border: 1px solid #fecaca; background: #fef2f2; padding: 1rem; border-radius: .75rem; margin-bottom: 1rem;">
  <p style="font-weight:600; color:#b91c1c; margin-bottom:.5rem;">Please fix the following:</p>
  <ul style="margin:0; padding-left:1.25rem; color:#b91c1c; font-size:.875rem;">
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

  {{-- ── Similar Problems Warning ── --}}
  @if(session('similarProblems'))
  <div style="border-radius: 1.5rem; border: 1px solid #fef3c7; background: #fffbeb; padding: 1.25rem;">
    <p style="font-weight: 600; color: #b45309; font-size: .875rem; margin-bottom: .5rem; display: flex; align-items: center; gap: .5rem;">
      <svg class="shrink-0" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
      Similar problems already reported
    </p>
    <ul style="margin: 0; padding: 0; display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; color: #b45309; margin-bottom: .5rem;">
      @foreach(session('similarProblems') as $problem)
        <li style="display: flex; gap: .5rem;"><span style="opacity: .6;">▸</span><a href="{{ route('feedback.show', $problem) }}" style="color: inherit; text-decoration: underline;">{{ $problem->title }}</a></li>
      @endforeach
    </ul>
    <p style="font-size: .75rem; color: #a16207;">You can support an existing problem instead of submitting a new one. If you attached files, please attach them again.</p>
    <div class="flex flex-wrap gap-3 mt-4">
      <a href="{{ route('feedback.index') }}" class="btn-back">Review existing problems</a>
      <button type="submit" name="force_submit" value="1" form="feedback-form" class="btn-submit">Submit anyway</button>
    </div>
  </div>
  @endif

  {{-- ── Main Form Card ── --}}
  <div x-data="{
      step: {{ old('current_step', session('similarProblems') ? 4 : session('current_step', 1)) }},
      showError: false,
      otherGroup: false,
      otherGroupVal: '',
      otherCategory: false,
      otherDepartment: {{ old('department') === 'Other' ? 'true' : 'false' }},
      currentProcessVal: @js(old('current_process')),
      otherProcess: {{ old('current_process') === 'Other' ? 'true' : 'false' }},

      canProceed() {
          if (this.step === 1) {
              const title = document.querySelector('[name=title]').value.trim() !== '';
              const checked = document.querySelectorAll('[name=\'affected_group[]\']:checked').length > 0;
              const category = document.querySelector('[name=category]').value;
              const categoryOther = document.querySelector('[name=category_other]');
              const needsCategoryOther = category === 'Other' && (!categoryOther || categoryOther.value.trim() === '');
              return title && checked && category && !needsCategoryOther;
          }
          if (this.step === 2) {
              return document.querySelector('[name=description]').value.trim() !== '' &&
                     document.querySelector('[name=impact]').value.trim() !== '';
          }
          if (this.step === 3) {
              const frequency = document.querySelector('[name=frequency]').value;
              const affectedUsers = document.querySelector('[name=affected_users]').value;
              const currentProcessEl = document.querySelector('[name=current_process]:checked');
              const currentProcess = currentProcessEl ? currentProcessEl.value : '';
              const currentProcessOther = document.querySelector('[name=current_process_other]');
              const needsProcessOther = currentProcess === 'Other' && (!currentProcessOther || currentProcessOther.value.trim() === '');
              return frequency !== '' && affectedUsers !== '' && currentProcess !== '' && !needsProcessOther;
          }
          return true;
      }
  }"
  style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06)">

    <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500"></div>

    <form id="feedback-form" method="POST" action="{{ route('feedback.store') }}" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8" @submit="document.querySelector('[name=current_step]').value = step">
      @csrf
      <input type="hidden" name="current_step" :value="step">

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
                  :class="step === {{ $n }} ? 'text-amber-500' : ''" style="color: step === {{ $n }} ? 'var(--amber)' : 'var(--muted2)'">{{ $label }}</span>
          </div>
          @endforeach
        </div>
        <p style="font-size: .75rem; color: var(--muted2); text-align: right; margin-top: .25rem;">
          Step <span x-text="step"></span> of 4
        </p>
      </div>

      {{-- ══════════════════════════════════════
           STEP 1 — Basic Information
      ══════════════════════════════════════ --}}
      <div x-show="step === 1" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Basic Information</span>
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
              <span style="color: #ef4444; font-weight: normal; margin-left: .125rem;">*</span>
            </label>

            <div class="grid grid-cols-2 gap-2">
              @foreach(['Students', 'Faculty', 'Staff', 'Administration'] as $group)
              <label class="check-card">
                <input type="checkbox" name="affected_group[]" value="{{ $group }}"
                  {{ is_array(old('affected_group')) && in_array($group, old('affected_group')) ? 'checked' : '' }}/>
                <span style="font-size: .875rem; font-weight: 500; color: var(--text);">{{ $group }}</span>
              </label>
              @endforeach

              {{-- Other — full width --}}
              <label class="check-card col-span-2" :class="otherGroup ? 'is-checked' : ''">
                <input type="checkbox" x-model="otherGroup"/>
                <span style="font-size: .875rem; font-weight: 500; color: var(--text);">Other</span>
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
              <p style="font-size: .6875rem; color: var(--muted2);">Specify who else is affected.</p>
            </div>

            <p style="font-size: .6875rem; color: var(--muted2); margin-top: .5rem;">Select all that apply.</p>
          </div>

          {{-- Affected Area — single select + Other --}}
          <div>
            <label class="field-label">
              Affected Area
              <span style="color: #ef4444; font-weight: normal; margin-left: .125rem;">*</span>
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
                     class="field" @input="showError = false"/>
              <p style="font-size: .6875rem; color: var(--muted2);">
                This will be reviewed by admin and may become a new category.
              </p>
              @error('category_other')
                <p style="font-size: .75rem; color: #ef4444;">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div>
            <label class="field-label">College / Office</label>

            <select name="department" class="field"
                    @change="otherDepartment = $event.target.value === 'Other'">
              <option value="">Select college or office (optional)…</option>
              <optgroup label="Colleges">
                <option value="CICS" {{ old('department') === 'CICS' ? 'selected' : '' }}>CICS</option>
                <option value="CHM" {{ old('department') === 'CHM' ? 'selected' : '' }}>CHM</option>
                <option value="CCJE" {{ old('department') === 'CCJE' ? 'selected' : '' }}>CCJE</option>
                <option value="CIT" {{ old('department') === 'CIT' ? 'selected' : '' }}>CIT</option>
                <option value="CTE" {{ old('department') === 'CTE' ? 'selected' : '' }}>CTE</option>
                <option value="CFAS" {{ old('department') === 'CFAS' ? 'selected' : '' }}>CFAS</option>
                <option value="CBEA" {{ old('department') === 'CBEA' ? 'selected' : '' }}>CBEA</option>
              </optgroup>
              <optgroup label="Offices">
                <option value="Registrar" {{ old('department') === 'Registrar' ? 'selected' : '' }}>Registrar</option>
                <option value="Guidance Office" {{ old('department') === 'Guidance Office' ? 'selected' : '' }}>Guidance Office</option>
              </optgroup>
              <option value="Other" {{ old('department') === 'Other' ? 'selected' : '' }}>Other</option>
            </select>

            <div x-show="otherDepartment" x-transition.opacity class="mt-2 space-y-1">
              <input type="text" name="department_other"
                     value="{{ old('department_other') }}"
                     placeholder="Enter the college or office…"
                     class="field"/>
              <p style="font-size: .6875rem; color: var(--muted2);">
                Optional: specify a custom college or office.
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
          <span class="section-head-title">Problem Details</span>
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
          <div>
            <label class="field-label">Supporting Evidence (Optional)</label>

            <div id="evidenceZone" class="field" style="min-height:100px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px;">
              <p class="text-sm">Drag and drop photos, screenshots or PDFs here, or</p>
              <div>
                <label for="evidenceInput" class="btn-next" style="cursor:pointer;">
                  Browse files
                </label>
                <input type="file" id="evidenceInput" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;" />
              </div>
              <p style="font-size: .6875rem; color: var(--muted2);">Optional: upload up to 5 files (JPG, PNG, WEBP or PDF, 10 MB each). You can leave this empty if you do not have files to attach.</p>
            </div>

            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="field" style="margin-top:10px;position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">

            <div id="evidencePreview" class="mt-3 grid grid-cols-3 gap-3"></div>

            @error('evidence.*')
              <p style="font-size: .75rem; color: #ef4444; margin-top: .5rem;">{{ $message }}</p>
            @enderror
            @error('evidence')
              <p style="font-size: .75rem; color: #ef4444; margin-top: .5rem;">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>

      {{-- ══════════════════════════════════════
           STEP 3 — Context
      ══════════════════════════════════════ --}}
      <div x-show="step === 3" x-transition.opacity>
        <div class="section-head">
          <span class="section-head-dot"></span>
          <span class="section-head-title">Context</span>
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
  <p style="font-size: .75rem; color: var(--muted2); margin-bottom: .75rem; margin-top: -.25rem;">
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
             @change="otherProcess = false"
             class="mt-0.5 shrink-0" style="accent-color:var(--amber); width:15px; height:15px;"/>
      <div class="flex items-start gap-3 flex-1 min-w-0">
        <i data-lucide="{{ $meta['icon'] }}"
           class="w-4 h-4 shrink-0 mt-0.5"
           style="color:var(--amber); opacity:.7;"></i>
        <div>
          <p style="font-size: .875rem; font-weight: 500; color: var(--text);">{{ $value }}</p>
          <p style="font-size: .75rem; color: var(--muted2); margin-top: .125rem;">{{ $meta['sub'] }}</p>
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
          <p style="font-size: .875rem; font-weight: 500; color: var(--text);">Other</p>
          <p style="font-size: .75rem; color: var(--muted2); margin-top: .125rem;">Describe how it's currently being handled.</p>
        </div>
      </div>
    </label>
 
  </div>

  <script>
    (function(){
      const input = document.getElementById('evidenceInput');
      const zone = document.getElementById('evidenceZone');
      const preview = document.getElementById('evidencePreview');

      zone.addEventListener('click', () => input.click());

      input.addEventListener('change', (e) => handleFiles(e.target.files));

      zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.style.opacity = '0.9'; });
      zone.addEventListener('dragleave', (e) => { e.preventDefault(); zone.style.opacity = '1'; });
      zone.addEventListener('drop', (e) => { e.preventDefault(); zone.style.opacity = '1'; handleFiles(e.dataTransfer.files); });

      function handleFiles(files){
  // Merge new files into the actual input's FileList so they're included on submit
  const dt = new DataTransfer();
  for (const f of input.files) dt.items.add(f);      // keep files already there
  for (const f of files) dt.items.add(f);            // add the newly dropped/selected ones
  input.files = dt.files;

  for (const file of files) {
    const reader = new FileReader();
    const card = document.createElement('div');
    card.className = 'rounded-lg border p-2';
    card.style.display = 'flex';
    card.style.flexDirection = 'column';
    card.style.alignItems = 'center';

    if (file.type.startsWith('image/')) {
      reader.onload = (ev) => {
        const img = document.createElement('img');
        img.src = ev.target.result;
        img.style.maxHeight = '90px';
        img.style.borderRadius = '8px';
        card.appendChild(img);
        addMeta();
      };
      reader.readAsDataURL(file);
    } else {
      const icon = document.createElement('div');
      icon.innerText = 'PDF';
      icon.style.fontWeight = '700';
      card.appendChild(icon);
      addMeta();
    }

    function addMeta(){
      const name = document.createElement('div');
      name.style.fontSize = '.8rem';
      name.style.marginTop = '6px';
      name.innerText = file.name + ' (' + (Math.round(file.size/1024/10)/100) + ' MB)';
      card.appendChild(name);

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn-back';
      remove.style.marginTop = '8px';
      remove.innerText = 'Remove';
      remove.addEventListener('click', () => {
        card.remove();
        // Also rebuild input.files without this one, or it stays "attached" after removal
        const dt2 = new DataTransfer();
        [...input.files].forEach(f => {
          if (!(f.name === file.name && f.size === file.size)) dt2.items.add(f);
        });
        input.files = dt2.files;
      });
      card.appendChild(remove);

      preview.appendChild(card);
    }
  }
}
    })();
  </script>
 
  {{-- Other text input ── --}}
  <div x-show="otherProcess" x-transition.opacity class="mt-3">
    <input type="text" name="current_process_other"
           value="{{ old('current_process_other') }}"
           placeholder="e.g. The department head handles it case by case…"
           class="field" @input="showError = false"/>
    @error('current_process_other')
      <p style="font-size: .75rem; color: #ef4444; margin-top: .5rem;">{{ $message }}</p>
    @enderror
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
          <span class="section-head-title">Review & Submit</span>
          <span class="section-head-line"></span>
        </div>

        <div style="border-radius: .75rem; border: 1px solid var(--border); background: var(--surface-alt); padding: 1.25rem; margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
          <p style="font-size: .75rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 600; color: var(--muted2); margin-bottom: .75rem;"> Your Submission Summary</p>
          
          {{-- Problem Title --}}
          <div style="border-top: 1px solid var(--border); padding-top: .75rem;">
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">Problem Title</p>
            <p id="review-title" style="font-size: .875rem; font-weight: 500; color: var(--text);">—</p>
          </div>
          
          {{-- Affected Groups --}}
          <div>
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">Who Is Affected</p>
            <p id="review-group" style="font-size: .875rem; font-weight: 500; color: var(--text);">—</p>
          </div>
          
          {{-- Category/Area --}}
          <div>
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">Affected Area</p>
            <p id="review-category" style="font-size: .875rem; font-weight: 500; color: var(--text);">—</p>
          </div>
          
          {{-- Description --}}
          <div>
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">Problem Description</p>
            <p id="review-description" style="font-size: .875rem; color: var(--text); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">—</p>
          </div>
          
          {{-- Impact --}}
          <div>
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">How It Affects People</p>
            <p id="review-impact" style="font-size: .875rem; color: var(--text); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">—</p>
          </div>
          
          {{-- Frequency & People Affected (side by side) --}}
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <div>
              <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">How Often</p>
              <p id="review-freq" style="font-size: .875rem; font-weight: 500; color: var(--text);">—</p>
            </div>
            <div>
              <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">People Affected</p>
              <p id="review-affected-users" style="font-size: .875rem; font-weight: 500; color: var(--text);">—</p>
            </div>
          </div>
          
          {{-- Current Process --}}
          <div>
            <p style="font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted2); margin-bottom: .25rem;">Current Solution/Process</p>
            <p id="review-process" style="font-size: .875rem; color: var(--text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">—</p>
          </div>
          
          {{-- Anonymous checkbox --}}
          <div style="border-top: 1px solid var(--border); padding-top: .75rem; display: flex; align-items: center; gap: .5rem;">
            <svg id="review-anon-icon" style="width: 1rem; height: 1rem; color: var(--muted2); display: none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M13.828 10.172a4 4 0 00-5.656 0l-4.242 4.242a4 4 0 105.656 5.656l4.242-4.242a4 4 0 00-5.656-5.656l4.242 4.242"/>
            </svg>
            <span id="review-anon-label" style="font-size: .75rem; color: var(--muted2);">—</span>
          </div>
        </div>

        <label style="display: flex; align-items: center; gap: .75rem; font-size: .875rem; color: var(--text); cursor: pointer; user-select: none;">
          <input type="checkbox" name="is_anonymous"
                 style="border-radius: 4px; accent-color: var(--amber); width: 1rem; height: 1rem;"/>
          Submit anonymously
        </label>
      </div>

      {{-- ── Navigation ── --}}
      <div>
        <p x-show="showError" style="font-size: .75rem; color: #ef4444; margin-bottom: .75rem; display: flex; align-items: center; gap: .375rem;">
          <svg class="shrink-0" style="width: .875rem; height: .875rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
          </svg>
          Please fill in all required fields before proceeding.
        </p>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: .75rem;">

          <button type="button" class="btn-back"
            @click="step = Math.max(step - 1, 1); showError = false; document.querySelector('[name=current_step]').value = step"
            x-show="step > 1">
            ← Back
          </button>

          <button type="button" class="btn-next" style="margin-left: auto;"
            x-show="step < 4"
            @click="canProceed()
              ? (step = Math.min(step + 1, 4), showError = false, updateReview(), document.querySelector('[name=current_step]').value = step)
              : showError = true">
            Next →
          </button>

          <button type="submit" class="btn-submit" style="margin-left: auto;" x-show="step === 4">
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

  // Description
  const desc = document.querySelector('[name=description]');
  document.getElementById('review-description').textContent = desc?.value?.trim() || '—';

  // Impact
  const impact = document.querySelector('[name=impact]');
  document.getElementById('review-impact').textContent = impact?.value?.trim() || '—';

  // Frequency
  const freq = document.querySelector('[name=frequency]');
  document.getElementById('review-freq').textContent = freq?.value || '—';

  // People Affected
  const affected = document.querySelector('[name=affected_users]');
  document.getElementById('review-affected-users').textContent = affected?.value || '—';

  // Current Process
const processEl = document.querySelector('[name=current_process]:checked');
const processOther = document.querySelector('[name=current_process_other]');
let processVal = processEl ? processEl.value : '';
if (processVal === 'Other' && processOther?.value.trim()) {
  processVal = processOther.value.trim();
}
document.getElementById('review-process').textContent = processVal || '—';

  // Anonymous status
  const isAnon = document.querySelector('[name=is_anonymous]');
  const anonIcon = document.getElementById('review-anon-icon');
  const anonLabel = document.getElementById('review-anon-label');
  if (isAnon?.checked) {
    anonIcon.style.display = 'block';
    anonLabel.textContent = '🔒 Will be submitted anonymously';
  } else {
    anonIcon.style.display = 'none';
    anonLabel.textContent = 'Will be submitted with your name';
  }
}

// ── Sync check-card highlight + update review on change ──
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.check-card input[type=checkbox]').forEach(input => {
    if (input.checked) input.closest('.check-card').classList.add('is-checked');
    input.addEventListener('change', () => {
      input.closest('.check-card').classList.toggle('is-checked', input.checked);
      updateReview();
    });
  });
  
  // Update review when any form field changes
  document.querySelectorAll('input[type=text], textarea, select').forEach(field => {
    field.addEventListener('change', updateReview);
    field.addEventListener('input', updateReview);
  });
  
  // Update review for radio buttons
  document.querySelectorAll('input[type=radio]').forEach(radio => {
    radio.addEventListener('change', updateReview);
  });
  
  // Update review for anonymous checkbox
  const anonCheckbox = document.querySelector('[name=is_anonymous]');
  if (anonCheckbox) {
    anonCheckbox.addEventListener('change', updateReview);
  }

  // ✅ Populate the review summary immediately in case the page loaded
  // directly on step 4 (e.g. after a failed validation redirect with old input)
  updateReview();
});
</script>

@endsection
