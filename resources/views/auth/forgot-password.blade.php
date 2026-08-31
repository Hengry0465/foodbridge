@extends('layouts.auth')

@section('title', 'Forgot Password — FoodBridge')

@section('header-link')
    <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
        Back to sign in
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="font-heading text-3xl font-bold text-emerald-800 mb-2">Forgot password?</h1>
        <p class="text-gray-600 mb-6">Enter your email and we will send you a reset link.</p>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-100 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                {{ session('status') }}
                @if (config('mail.default') === 'log')
                    <p class="mt-2 text-xs">Local dev: open <code class="bg-white/60 px-1 rounded">storage/logs/laravel.log</code> to find the reset link.</p>
                @endif
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block font-medium mb-2 text-gray-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="you@example.com"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror"
                >
            </div>

            <button type="submit" class="w-full bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition hover:bg-emerald-800">
                Send reset link
            </button>
        </form>
    </div>
@endsection
