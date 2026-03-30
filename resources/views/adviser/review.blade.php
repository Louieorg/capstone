@extends('layouts.app')

@section('title', 'Adviser Review')

@section('content')

<div class="max-w-4xl mx-auto">

<h2 class="text-xl font-bold mb-6">Review Capstone Ideas</h2>

@foreach($categories as $category)

<div class="bg-white border rounded-xl p-5 mb-4">

<h3 class="font-semibold text-lg mb-2">
{{ $category }}
</h3>

<p class="text-sm text-gray-600 mb-3">
Click to review generated ideas for this category.
</p>

<a href="{{ route('feedback.category', $category) }}"
   class="bg-blue-500 text-white px-4 py-2 rounded text-sm">
    View Ideas
</a>

</div>

@endforeach

</div>

@endsection