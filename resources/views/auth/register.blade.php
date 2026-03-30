@extends('layouts.app')

@section('content')

<div class="max-w-md mx-auto">

<h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">
Create Account
</h2>

<div class="bg-white border rounded-lg p-6 shadow-sm">

<form method="POST" action="{{ route('register') }}">
@csrf

<div class="mb-4">

<label class="block text-sm text-gray-600 mb-1">
Name
</label>

<input
type="text"
name="name"
required
class="w-full border rounded px-3 py-2 text-sm"
/>

</div>

<div class="mb-4">

<label class="block text-sm text-gray-600 mb-1">
Email
</label>

<input
type="email"
name="email"
required
class="w-full border rounded px-3 py-2 text-sm"
/>

</div>

<div class="mb-4">

<label class="block text-sm text-gray-600 mb-1">
Password
</label>

<input
type="password"
name="password"
required
class="w-full border rounded px-3 py-2 text-sm"
/>

</div>

<div class="mb-6">

<label class="block text-sm text-gray-600 mb-1">
Confirm Password
</label>

<input
type="password"
name="password_confirmation"
required
class="w-full border rounded px-3 py-2 text-sm"
/>

</div>

<button
class="w-full bg-amber-600 hover:bg-amber-700 text-white py-2 rounded transition">
Register
</button>

</form>

</div>

<p class="text-sm text-center text-gray-500 mt-4">
Already have an account?
<a href="{{ route('login') }}" class="text-amber-600 hover:underline">
Login
</a>
</p>

</div>

@endsection