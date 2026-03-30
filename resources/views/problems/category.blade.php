@extends('layouts.app')
 
@section('title', $category . ' Issues')
@section('subtitle', 'Recurring problems and suggested capstone solution')
 
@section('content')
 
<style>
  :root {
    --amber: #fbb034;
    --amber-dim: rgba(251,176,52,0.10);
    --amber-border: rgba(251,176,52,0.22);
  }
 
  /* ── Accordion toggle ── */
  .accordion-btn { display:flex; justify-content:space-between; align-items:center; width:100%; font-weight:600; font-size:.875rem; cursor:pointer; padding:.5rem 0; color:inherit; background:none; border:none; text-align:left; }
  .accordion-icon { width:16px; height:16px; transition:transform .25s; }
  .accordion-btn[aria-expanded="true"] .accordion-icon { transform:rotate(180deg); }
 
  /* ── Star rating ── */
  .stars { color: var(--amber); letter-spacing:.05em; }
 
  /* ── Fade-up entry ── */
  @keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .anim-1 { animation: fadeInUp .5s ease both; }
  .anim-2 { animation: fadeInUp .5s .1s ease both; }
  .anim-3 { animation: fadeInUp .5s .2s ease both; }
  .anim-4 { animation: fadeInUp .5s .3s ease both; }
 
  /* ── Form inputs ── */
  .form-input {
    width:100%; border:1px solid #d1d5db; border-radius:10px;
    padding:9px 13px; font-size:.8125rem; outline:none;
    transition:border-color .2s, box-shadow .2s;
    background:transparent;
  }
  .form-input:focus { border-color:var(--amber); box-shadow:0 0 0 3px var(--amber-dim); }
  .dark .form-input { border-color:#374151; color:#e5e7eb; }
 
  /* ── Comparison table ── */
  .cmp-table th { font-size:.7rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; padding:.6rem 1rem; }
  .cmp-table td { padding:.6rem 1rem; font-size:.8125rem; }
  .cmp-winner { color:#16a34a; font-weight:700; }
</style>
 
<div class="max-w-4xl mx-auto space-y-8">
 
{{-- ══════════════════════════════ --}}
{{-- ⭐  TOP RECOMMENDED IDEA      --}}
{{-- ══════════════════════════════ --}}
@if(isset($topIdea))
<div x-data="{ detailsOpen: false, explainOpen: false, objOpen: false, evalOpen: false }"
     class="anim-1 relative rounded-2xl overflow-hidden border"
     style="border-color:var(--amber-border); background:linear-gradient(135deg,rgba(251,176,52,.07) 0%,transparent 60%);">
 
  {{-- Amber top bar --}}
  <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500"></div>
 
  <div class="p-6 sm:p-8">
 
    {{-- Header row --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-1">
      <div class="flex items-center gap-2">
        <span class="text-amber-400 text-lg">⭐</span>
        <span class="text-xs font-semibold uppercase tracking-widest" style="color:var(--amber);">Top Recommended Idea</span>
      </div>
      <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
        Based on real reported problems and evaluation data. Adjust as needed for your project goals.
      </p>
    </div>
 
    {{-- Title --}}
    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-4 mb-3 leading-snug" style="font-family:'Sora',sans-serif;">
      {{ $topIdea['title'] ?? 'No title available' }}
    </h2>
 
    {{-- Badges --}}
    <div class="flex flex-wrap gap-2 mb-3">
      <span class="text-xs font-semibold px-3 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">
        {{ $topIdea['priority'] ?? 'Low' }} Priority
      </span>
      <span class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
        ⚠ {{ $topIdea['severity_level'] ?? 'Low' }} Severity
      </span>
      <span class="text-xs font-semibold px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
        ✅ {{ $topIdea['confidence_level'] ?? 'Low' }} Confidence
      </span>
    </div>
 
    {{-- Confidence & severity text --}}
    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
      ✅ {{ $topIdea['confidence_explanation'] ?? '' }}<br>
      ⚠ {{ $topIdea['severity_explanation'] ?? '' }}
    </p>
 
    {{-- Description --}}
    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mt-4">
      {{ $topIdea['description'] ?? 'No description available' }}
    </p>
 
    {{-- Impact --}}
    @if(isset($topIdea['impact_simulation']['message']))
    <p class="mt-3 text-sm font-medium text-green-600 dark:text-green-400">
      {{ $topIdea['impact_simulation']['message'] }}
    </p>
    @endif
 
    {{-- Toggle Details --}}
    <button class="accordion-btn mt-5 text-sm font-semibold" style="color:var(--amber);"
      @click="detailsOpen = !detailsOpen" :aria-expanded="detailsOpen">
      <span x-text="detailsOpen ? 'Hide Details' : 'View Details'"></span>
      <svg class="accordion-icon" :class="detailsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </button>
 
    {{-- ── Expandable Details ── --}}
    <div x-show="detailsOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-180"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="mt-6 space-y-4">
 
      {{-- 🧠 Why this idea --}}
      <div class="border border-gray-100 dark:border-slate-700 rounded-xl overflow-hidden">
        <button class="accordion-btn px-5 py-4 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 transition"
          @click="explainOpen = !explainOpen" :aria-expanded="explainOpen">
          <span>🧠 Why this idea?</span>
          <svg class="accordion-icon" :class="explainOpen ? 'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="explainOpen" x-transition class="px-5 pb-5 border-t border-gray-100 dark:border-slate-700 pt-4 bg-white/50 dark:bg-white/5">
          <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $topIdea['explanation']['summary'] ?? '' }}</p>
          <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs text-gray-600 dark:text-gray-400 mb-4">
            <p><span class="font-semibold text-gray-700 dark:text-gray-300">📊 Reports:</span> {{ $topIdea['explanation']['factors']['reports'] ?? 0 }}</p>
            <p><span class="font-semibold text-gray-700 dark:text-gray-300">👍 Votes:</span> {{ $topIdea['explanation']['factors']['votes'] ?? 0 }}</p>
            <p><span class="font-semibold text-gray-700 dark:text-gray-300">⏱ Frequency:</span> {{ $topIdea['explanation']['factors']['frequency_score'] ?? 0 }}</p>
            <p><span class="font-semibold text-gray-700 dark:text-gray-300">👥 Impact:</span> {{ $topIdea['explanation']['factors']['impact_score'] ?? 0 }}</p>
            <p><span class="font-semibold text-gray-700 dark:text-gray-300">🎯 Users:</span> {{ $topIdea['explanation']['factors']['top_affected_group'] ?? 'N/A' }}</p>
          </div>
          <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
            <p>📌 {{ $topIdea['explanation']['reasoning']['impact'] ?? '' }}</p>
            <p>📌 {{ $topIdea['explanation']['reasoning']['frequency'] ?? '' }}</p>
            <p>📌 {{ $topIdea['explanation']['reasoning']['reports'] ?? '' }}</p>
            <p>📌 {{ $topIdea['explanation']['reasoning']['votes'] ?? '' }}</p>
          </div>
        </div>
      </div>
 
      {{-- 🎯 Objectives --}}
      <div class="border border-gray-100 dark:border-slate-700 rounded-xl overflow-hidden">
        <button class="accordion-btn px-5 py-4 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 transition"
          @click="objOpen = !objOpen" :aria-expanded="objOpen">
          <span>🎯 Objectives</span>
          <svg class="accordion-icon" :class="objOpen ? 'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="objOpen" x-transition class="px-5 pb-5 border-t border-gray-100 dark:border-slate-700 pt-4 bg-white/50 dark:bg-white/5">
          <div class="grid md:grid-cols-2 gap-5 text-sm">
            <div>
              <p class="font-semibold text-gray-800 dark:text-gray-200 mb-2">General Objective</p>
              <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ $topIdea['general_objective'] ?? 'No objective available' }}</p>
            </div>
            <div>
              <p class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Specific Objectives</p>
              <ul class="space-y-1.5 text-gray-600 dark:text-gray-400">
                @foreach($topIdea['specific_objectives'] ?? [] as $obj)
                  <li class="flex gap-2"><span class="text-amber-400 mt-0.5 shrink-0">▸</span>{{ $obj }}</li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      </div>
 
      {{-- 📊 Evaluation --}}
      @if(isset($topIdea['evaluation']))
      <div class="border border-gray-100 dark:border-slate-700 rounded-xl overflow-hidden">
        <button class="accordion-btn px-5 py-4 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 transition"
          @click="evalOpen = !evalOpen" :aria-expanded="evalOpen">
          <span>📊 Evaluation</span>
          <svg class="accordion-icon" :class="evalOpen ? 'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div x-show="evalOpen" x-transition class="px-5 pb-5 border-t border-gray-100 dark:border-slate-700 pt-4 bg-white/50 dark:bg-white/5">
          <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            @foreach(['feasibility' => 'Feasibility', 'impact' => 'Impact', 'complexity' => 'Complexity', 'innovation' => 'Innovation'] as $key => $label)
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-slate-700">
              <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
              <span class="stars text-sm">{{ str_repeat('★', $topIdea['evaluation'][$key]) }}{{ str_repeat('☆', 5 - $topIdea['evaluation'][$key]) }}</span>
            </div>
            @endforeach
          </div>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
              Overall Score: <span style="color:var(--amber);">{{ $topIdea['evaluation']['overall_score'] }}</span>
            </span>
            <span class="text-sm font-bold px-3 py-1 rounded-full
              @if($topIdea['evaluation']['recommendation'] === 'Highly Recommended') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
              @elseif($topIdea['evaluation']['recommendation'] === 'Recommended') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
              @else bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 @endif">
              {{ $topIdea['evaluation']['recommendation'] }}
            </span>
          </div>
        </div>
      </div>
      @endif
 
      {{-- Adviser Review --}}
      @if(isset($topReview))
      <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-5">
        <h5 class="text-sm font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-2 mb-2">
          <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Adviser Review
        </h5>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ $topReview->comment }}</p>
        <span class="text-xs font-bold
          @if($topReview->recommendation == 'Recommended') text-green-600 dark:text-green-400
          @elseif($topReview->recommendation == 'Needs Revision') text-amber-600 dark:text-amber-400
          @else text-red-600 dark:text-red-400 @endif">
          {{ $topReview->recommendation }}
        </span>
      </div>
      @endif
 
    </div>{{-- /detailsOpen --}}
 
    {{-- ── Action Buttons ── --}}
    <div class="mt-6 flex flex-wrap gap-3">
      @auth
      <form method="POST" action="{{ route('idea.save') }}">
        @csrf
        <input type="hidden" name="title" value="{{ $topIdea['title'] ?? '' }}">
        <input type="hidden" name="description" value="{{ $topIdea['description'] ?? '' }}">
        <input type="hidden" name="category" value="{{ $category }}">
        <button class="px-5 py-2 rounded-xl text-sm font-semibold text-black transition shadow-[0_4px_14px_rgba(251,176,52,0.3)] hover:shadow-[0_6px_20px_rgba(251,176,52,0.45)] hover:-translate-y-0.5 active:scale-95"
          style="background:linear-gradient(to right,#fbb034,#f97316);">
          Save Best Idea
        </button>
      </form>
      @else
      <p class="text-xs text-gray-400 dark:text-gray-500 self-center">Login to save this idea.</p>
      @endauth
 
      @auth
      @if(auth()->user()->role === 'adviser')
      <button onclick="document.getElementById('reviewForm').classList.toggle('hidden')"
        class="px-5 py-2 rounded-xl text-sm font-semibold text-gray-200 bg-slate-700 hover:bg-slate-600 transition hover:-translate-y-0.5 active:scale-95">
        Add Review
      </button>
      @endif
      @endauth
    </div>
 
    {{-- ── Adviser Review Form ── --}}
    @auth
    @if(auth()->user()->role === 'adviser')
    <div id="reviewForm" class="hidden mt-6 rounded-2xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/10 p-6">
      <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-2 mb-4">
        <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Adviser Evaluation Form
      </h4>
      <form method="POST" action="{{ route('adviser.review') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="idea_title" value="{{ $topIdea['title'] }}">
        <input type="hidden" name="category" value="{{ $category }}">
 
        <textarea name="comment" rows="3" placeholder="Enter your feedback here…"
          class="form-input resize-none" required></textarea>
 
        <select name="recommendation" class="form-input" required>
          <option value="">Select Recommendation</option>
          <option value="Recommended">Recommended</option>
          <option value="Needs Revision">Needs Revision</option>
          <option value="Not Recommended">Not Recommended</option>
        </select>
 
        <div class="grid grid-cols-2 gap-4">
          @foreach(['feasibility' => 'Feasibility (1=Hard, 5=Easy)', 'impact' => 'Impact (1=Low, 5=High)', 'complexity' => 'Complexity (1=Hard, 5=Easy)', 'innovation' => 'Innovation (1=Low, 5=High)'] as $field => $label)
          <div>
            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ $label }}</label>
            <input type="number" name="{{ $field }}" min="1" max="5" class="form-input" required/>
          </div>
          @endforeach
        </div>
 
        <button class="px-5 py-2 rounded-xl text-sm font-semibold text-black transition shadow-[0_4px_14px_rgba(251,176,52,0.3)] hover:shadow-[0_6px_20px_rgba(251,176,52,0.45)] active:scale-95"
          style="background:linear-gradient(to right,#fbb034,#f97316);">
          Submit Review
        </button>
      </form>
    </div>
    @endif
    @endauth
 
  </div>{{-- /card body --}}
</div>{{-- /topIdea card --}}
@endif
 
 
{{-- ══════════════════════════════ --}}
{{-- 🔥  IDEA COMPARISON           --}}
{{-- ══════════════════════════════ --}}
@if(isset($topIdea) && count($otherIdeas) > 0)
<div class="anim-2 rounded-2xl border border-gray-100 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-800/60">
  <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center gap-2">
    <span class="text-base">🔥</span>
    <h3 class="font-semibold text-gray-800 dark:text-gray-100" style="font-family:'Sora',sans-serif; font-size:.95rem;">Idea Comparison</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="cmp-table w-full text-sm">
      <thead class="bg-gray-50 dark:bg-slate-700/60 text-gray-500 dark:text-gray-400">
        <tr>
          <th class="text-left border-b border-gray-100 dark:border-slate-700">Criteria</th>
          <th class="text-center border-b border-gray-100 dark:border-slate-700" style="color:var(--amber);">⭐ Top Idea</th>
          <th class="text-center border-b border-gray-100 dark:border-slate-700 text-gray-400">Alternative</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50 dark:divide-slate-700">
        @foreach(['score'=>'Score','feasibility'=>'Feasibility','impact'=>'Impact','complexity'=>'Complexity','severity'=>'Severity','confidence'=>'Confidence'] as $key => $label)
        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
          <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-medium">{{ $label }}</td>
          <td class="px-4 py-3 text-center cmp-winner">{{ $topIdea['comparison'][$key] ?? '—' }}</td>
          <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">{{ $otherIdeas[0]['comparison'][$key] ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif
 
 
{{-- ══════════════════════════════ --}}
{{-- 📦  OTHER IDEAS               --}}
{{-- ══════════════════════════════ --}}
@if(isset($otherIdeas) && count($otherIdeas) > 0)
<div class="anim-3">
  <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">
    Other Suggested Ideas
  </h3>
  <div class="grid sm:grid-cols-2 gap-4">
    @foreach($otherIdeas as $idea)
    <div class="group relative rounded-2xl border border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800/60 p-5 hover:-translate-y-1 hover:shadow-xl hover:border-amber-200 dark:hover:border-amber-800/50 transition-all duration-250">
 
      <div class="flex justify-between items-center mb-3">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:var(--amber-dim); color:var(--amber); border:1px solid var(--amber-border);">Alternative</span>
        <span class="text-xs font-semibold
          @if(($idea['priority'] ?? '') == 'High') text-red-500
          @elseif(($idea['priority'] ?? '') == 'Medium') text-amber-500
          @else text-gray-400 @endif">
          {{ $idea['priority'] ?? 'Low' }} Priority
        </span>
      </div>
 
      <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm leading-snug" style="font-family:'Sora',sans-serif;">
        {{ $idea['title'] ?? 'No title' }}
      </h4>
      <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-3">
        {{ $idea['description'] ?? 'No description available' }}
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Score: <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $idea['score'] ?? 0 }}</span></p>
 
      @auth
      <form method="POST" action="{{ route('idea.save') }}">
        @csrf
        <input type="hidden" name="title" value="{{ $idea['title'] ?? '' }}">
        <input type="hidden" name="description" value="{{ $idea['description'] ?? '' }}">
        <input type="hidden" name="category" value="{{ $category }}">
        <button class="w-full py-2 rounded-xl text-xs font-semibold text-black transition active:scale-95 hover:-translate-y-0.5"
          style="background:linear-gradient(to right,#fbb034,#f97316); box-shadow:0 4px 12px rgba(251,176,52,.25);">
          Save Idea
        </button>
      </form>
      @endauth
 
    </div>
    @endforeach
  </div>
</div>
@endif
 
 
{{-- ══════════════════════════════ --}}
{{-- 📊  SUPPORTING REPORTS        --}}
{{-- ══════════════════════════════ --}}
<div class="anim-4">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold text-gray-800 dark:text-gray-100" style="font-family:'Sora',sans-serif; font-size:.95rem;">
      Reports Supporting This Issue
    </h3>
    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400">
      {{ $feedbacks->count() }} reports
    </span>
  </div>
 
  <div class="space-y-3">
    @foreach($feedbacks as $feedback)
    <div class="rounded-2xl border border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800/60 p-5 hover:-translate-y-0.5 hover:shadow-md hover:border-amber-200 dark:hover:border-amber-800/40 transition-all duration-200">
      <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
        {{ $feedback->description }}
      </p>
      <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500">
        <span>{{ $feedback->created_at->diffForHumans() }}</span>
        @if(isset($feedback->votes_count))
        <span class="flex items-center gap-1 font-medium" style="color:var(--amber);">
          ▲ {{ $feedback->votes_count }}
        </span>
        @endif
      </div>
    </div>
    @endforeach
  </div>
  
</div>
 
</div>{{-- /max-w-4xl --}}
 
@endsection