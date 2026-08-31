@extends('layouts.auth')

@section('title', 'Sign In — FoodBridge')

@section('header-link')
    <a href="{{ route('register') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
        Create account
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="font-heading text-3xl font-bold text-emerald-800 mb-2">Welcome back</h1>
        <p class="text-gray-600 mb-6">Sign in to your FoodBridge account.</p>

        <form action="{{ url('/login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block font-medium mb-2 text-gray-700">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="your_username"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('username') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="password" class="block font-medium mb-2 text-gray-700">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Your password"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror"
                >
            </div>

            <div class="flex items-center justify-between">
                <label for="remember" class="flex items-center">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                    >
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="w-full bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition hover:bg-emerald-800">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:text-emerald-900">Sign up</a>
        </p>
    </div>
@endsection
