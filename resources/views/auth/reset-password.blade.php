@extends('layouts.auth')

@section('title', 'Reset Password — FoodBridge')

@section('header-link')
    <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
        Back to sign in
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="font-heading text-3xl font-bold text-emerald-800 mb-2">Reset password</h1>
        <p class="text-gray-600 mb-6">Choose a new password for your account.</p>

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block font-medium mb-2 text-gray-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="password" class="block font-medium mb-2 text-gray-700">New password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block font-medium mb-2 text-gray-700">Confirm password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>

            <button type="submit" class="w-full bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition hover:bg-emerald-800">
                Reset password
            </button>
        </form>
    </div>
@endsection
