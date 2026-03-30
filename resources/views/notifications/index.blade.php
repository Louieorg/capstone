@extends('layouts.app')

@section('title','Notifications')

@section('content')

<div class="max-w-3xl mx-auto space-y-4">

@forelse($notifications as $notification)

<div class="bg-white border rounded-lg p-4">

{{ $notification->data['message'] }}

</div>

@empty

<p>No notifications.</p>

@endforelse

</div>

@endsection