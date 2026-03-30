@extends('layouts.app')

@section('title','Login')
@section('subtitle','Access your LIKHA account')

@section('content')

<div class="max-w-md mx-auto">

<div class="bg-white border rounded-xl p-8 shadow-sm">

<h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">
Login to LIKHA
</h2>

<form method="POST" action="{{ route('login') }}" class="space-y-5">
@csrf

<!-- Email -->

<div>

<label class="block text-sm font-semibold text-gray-700 mb-2">
Email Address
</label>

<input
type="email"
name="email"
required
class="w-full border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
/>

</div>

<!-- Password -->

<div>

<label class="block text-sm font-semibold text-gray-700 mb-2">
Password
</label>

<input
type="password"
name="password"
required
class="w-full border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
/>

</div>

<!-- Login Button -->

<button
class="w-full bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-lg font-medium text-sm transition">

Login

</button>

</form>

<!-- Divider -->

<div class="flex items-center my-6">

<div class="flex-grow border-t"></div>

<span class="mx-3 text-gray-400 text-sm">
OR
</span>

<div class="flex-grow border-t"></div>

</div>

<!-- Google Login -->

<a href="{{ route('google.login') }}"
class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg py-2.5 hover:bg-gray-50 transition">

<img
src="https://developers.google.com/identity/images/g-logo.png"
alt="Google"
class="w-5 h-5">

<span class="text-gray-700 text-sm font-medium">
Login with Google
</span>

</a>

</div>

<p class="text-sm text-center text-gray-500 mt-6">

Don't have an account?

<a href="{{ route('register') }}" class="text-amber-600 hover:underline">
Register
</a>

</p>

</div>

@endsection
