@extends('layouts.app')

@section('title','My Saved Ideas')

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6">

{{-- HEADER --}}
<div class="mb-6 opacity-0 translate-y-4 
animate-[fadeInUp_0.6s_ease-out_forwards]">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        My Saved Ideas
    </h2>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Review and compare your saved capstone ideas
    </p>
</div>

@forelse($ideas as $idea)

<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-5 sm:p-6 mb-5 hover:shadow-md transition">

    {{-- TOP --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">

        <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
            {{ $idea->title }}
        </h3>

        <span class="text-xs bg-amber-100 dark:bg-amber-700 text-amber-700 dark:text-white px-3 py-1 rounded-full w-fit">
            {{ $idea->category }}
        </span>

        <form method="POST" action="{{ route('idea.updateStatus', $idea->id) }}">
    @csrf @method('PATCH')
    <select name="status" onchange="this.form.submit()"
        class="text-xs border border-gray-200 dark:border-slate-700 rounded-lg px-2 py-1
               bg-transparent focus:ring-amber-400 focus:border-amber-400 cursor-pointer">
        @foreach(['Exploring', 'Adopted', 'In Progress', 'Completed'] as $s)
        <option value="{{ $s }}" {{ $idea->status === $s ? 'selected' : '' }}>
            {{ $s }}
        </option>
        @endforeach
    </select>
</form>

    </div>

    {{-- DESCRIPTION --}}
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
        {{ $idea->description }}
    </p>

    {{-- ========================= --}}
    {{-- 🎯 OBJECTIVES --}}
    {{-- ========================= --}}
    @php
        $specificObjectives = [];

        if (!empty($idea->specific_objectives)) {
            $decoded = json_decode($idea->specific_objectives, true);
            if (is_array($decoded)) {
                $specificObjectives = $decoded;
            }
        }
    @endphp

    @if(!empty($idea->general_objective) || count($specificObjectives))

    <div class="bg-gray-50 dark:bg-gray-900/40 border dark:border-gray-700 rounded-lg p-4 mb-4">

        {{-- GENERAL OBJECTIVE --}}
        @if(!empty($idea->general_objective))
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
            General Objective
        </p>

        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
            {{ $idea->general_objective }}
        </p>
        @endif

        {{-- SPECIFIC OBJECTIVES --}}
        @if(count($specificObjectives))
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
            Specific Objectives
        </p>

        <ul class="list-disc ml-5 text-sm text-gray-700 dark:text-gray-300 space-y-1">
            @foreach($specificObjectives as $obj)
                <li>{{ $obj }}</li>
            @endforeach
        </ul>
        @endif


    </div>

    @endif

    {{-- FOOTER --}}
    <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
        <span>
            Saved {{ $idea->created_at->diffForHumans() }}
        </span>
    </div>

</div>

@empty

<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-8 text-center">
    <p class="text-gray-500 dark:text-gray-400">
        No saved ideas yet.
    </p>
</div>

@endforelse

</div>

@endsection