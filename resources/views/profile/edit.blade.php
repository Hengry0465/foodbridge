@extends('layouts.auth')

@section('title', 'Edit Profile — FoodBridge')

@section('header-link')
    @php
        $dashboardRoute = match(auth()->user()->role->value) {
            'admin' => route('admin.dashboard'),
            'recipient' => route('recipient.dashboard'),
            default => route('donor.dashboard'),
        };
    @endphp
    <a href="{{ $dashboardRoute }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
        Back to dashboard
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <h1 class="font-heading text-3xl font-bold text-emerald-800 mb-2">Edit profile</h1>
        <p class="text-gray-600 mb-6">Update your name, email, or password.</p>

        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-gray-700">
            <p><span class="font-medium">Role:</span> {{ auth()->user()->role->value }} (cannot be changed here)</p>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block font-medium mb-2 text-gray-700">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label for="email" class="block font-medium mb-2 text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="pt-2 border-t border-gray-100">
                <p class="text-sm text-gray-500 mb-3">Leave blank to keep your current password.</p>
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block font-medium mb-2 text-gray-700">New password</label>
                        <input id="password" name="password" type="password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block font-medium mb-2 text-gray-700">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition hover:bg-emerald-800">
                Save changes
            </button>
        </form>
    </div>
@endsection
