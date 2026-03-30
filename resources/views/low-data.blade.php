@extends('layouts.app')

@section('content')

<h2 class="text-xl font-bold mb-4">
    Not Enough Data Yet
</h2>

<p>
This category does not have enough reports to generate a capstone idea.
</p>

<a href="{{ route('feedback.summary') }}"
   class="text-blue-600 mt-4 inline-block">
    Back to Summary
</a>

@endsection