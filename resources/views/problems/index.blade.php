@extends('layouts.app')

@section('title','Reported Problems')
@section('subtitle','Explore campus issues reported by students')

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 opacity-0 translate-y-4 
animate-[fadeInUp_0.6s_ease-out_forwards]">

{{-- SEARCH + FILTER --}}
<form method="GET"
class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
rounded-xl p-4 mb-8 flex flex-col md:flex-row gap-3 items-center 
shadow-sm transition-all duration-300 hover:shadow-lg">

<input
type="text"
name="search"
value="{{ request('search') }}"
placeholder="Search campus problems..."
class="flex-1 w-full border border-gray-300 dark:border-gray-600 
bg-white dark:bg-gray-900 text-gray-800 dark:text-white 
rounded-lg px-4 py-2 text-sm 
focus:outline-none focus:ring-2 focus:ring-amber-400 
focus:shadow-[0_0_10px_rgba(251,191,36,0.25)] 
transition-all duration-200"
/>

<select name="category"
class="w-full md:w-auto border border-gray-300 dark:border-gray-600 
bg-white dark:bg-gray-900 text-gray-800 dark:text-white 
rounded-lg px-3 py-2 text-sm 
focus:outline-none focus:ring-2 focus:ring-amber-400 
transition-all duration-200">

<option value="">All Categories</option>

@foreach($categories as $cat)
<option value="{{ $cat }}"
{{ request('category') == $cat ? 'selected' : '' }}>
{{ $cat }}
</option>
@endforeach

</select>

<button
class="w-full md:w-auto bg-gradient-to-r from-amber-400 to-orange-500 
hover:from-amber-300 hover:to-orange-400 
active:scale-[0.98] 
text-white px-5 py-2 rounded-lg text-sm font-medium 
shadow-md hover:shadow-lg 
transition-all duration-200">
Filter
</button>

</form>

{{-- EMPTY STATE --}}
@if($feedbacks->isEmpty())

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
<p class="text-gray-500 dark:text-gray-400">
No problems found.
</p>
</div>

@else

{{-- PROBLEM LIST --}}
<div class="space-y-5">

@foreach($feedbacks as $feedback)

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
rounded-xl p-5 sm:p-6 
transition-all duration-300 hover:shadow-xl hover:-translate-y-1">

{{-- TOP --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">

<span class="text-xs font-medium 
bg-amber-100 dark:bg-amber-700 
text-amber-700 dark:text-white 
px-3 py-1 rounded-full w-fit">
{{ $feedback->category }}
</span>

<span class="text-xs text-gray-400 dark:text-gray-500">
{{ $feedback->created_at->diffForHumans() }}
</span>

</div>

{{-- DESCRIPTION --}}
<p class="text-gray-700 dark:text-gray-300 mb-4 leading-relaxed text-sm sm:text-base">
{{ $feedback->description }}
</p>


{{-- ACTIONS --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

<div class="flex items-center gap-4">

<form method="POST" action="{{ route('feedback.vote', $feedback->id) }}">
@csrf

<button 
class="text-sm font-medium transition-all duration-200
@if($feedback->votes->count())
text-amber-600 dark:text-amber-400
@else
text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400
@endif
hover:scale-105">

@if($feedback->votes->count())
▲ Unvote
@else
▲ Upvote
@endif

</button>

</form>

<span class="text-sm text-gray-500 dark:text-gray-400">
{{ $feedback->votes_count }} votes
</span>

</div>

<a
href="{{ route('feedback.category', $feedback->category) }}"
class="text-sm text-amber-600 dark:text-amber-400 
hover:text-amber-500 transition font-medium">
View related →
</a>

</div>

</div>

@endforeach

</div>

@endif

</div>

{{-- PAGINATION --}}
<div class="mt-6 flex justify-center">
    {{ $feedbacks->withQueryString()->links() }}
</div>

@endsection