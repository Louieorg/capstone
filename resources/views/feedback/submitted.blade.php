@extends('layouts.app')

@section('title','Submission Successful')
@section('subtitle','Your report has been received')

@section('content')

<div class="max-w-xl mx-auto">

<div class="bg-white border rounded-xl p-8 text-center">

<h2 class="text-xl font-semibold text-gray-800 mb-3">
Problem Submitted Successfully
</h2>

<p class="text-sm text-gray-600 mb-6">
Your report has been received and is currently under review by the administrator.
Once approved, it will appear in the problem feed where students can view and support it.
</p>

<div class="flex justify-center gap-4">

<a href="{{ route('feedback.index') }}"
class="bg-gray-200 hover:bg-gray-300 px-5 py-2 rounded-lg text-sm">

View Problems

</a>

<a href="{{ route('feedback.create') }}"
class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg text-sm">

Submit Another Problem

</a>

</div>

</div>

</div>

@endsection