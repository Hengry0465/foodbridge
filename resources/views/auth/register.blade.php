@extends('layouts.auth')

@section('title', 'Sign Up — FoodBridge')

@section('header-link')
    <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
        Sign in
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="font-heading text-3xl font-bold text-emerald-800 mb-2">Join FoodBridge</h1>
        <p class="text-gray-600 mb-6">Create your account to get started.</p>

        <form action="{{ url('/register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block font-medium mb-2 text-gray-700">Full Name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your name"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('name') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="username" class="block font-medium mb-2 text-gray-700">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autocomplete="username"
                    placeholder="your_username"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('username') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="email" class="block font-medium mb-2 text-gray-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="password" class="block font-medium mb-2 text-gray-700">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block font-medium mb-2 text-gray-700">Confirm Password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>

            <div>
                <label for="role" class="block font-medium mb-2 text-gray-700">I am a...</label>
                <select
                    id="role"
                    name="role"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('role') border-red-400 @enderror"
                >
                    <option value="" disabled @selected(old('role') === null)>Select your role</option>
                    <option value="donor" @selected(old('role') === 'donor')>Donor — I want to donate surplus food</option>
                    <option value="recipient" @selected(old('role') === 'recipient')>Recipient — I represent an organisation seeking food</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    Admin accounts are created internally and are not available via self sign-up.
                </p>
            </div>

            <button type="submit" class="w-full bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition hover:bg-emerald-800">
                Create Account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:text-emerald-900">Sign in</a>
        </p>
    </div>
@endsection
