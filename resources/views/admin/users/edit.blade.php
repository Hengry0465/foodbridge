@extends('layouts.admin')

@section('title', 'Manage User')

@section('content')
    <div class="max-w-xl">
        <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" class="text-emerald-700 text-sm hover:underline">← Back to users</a>

        <h2 class="font-heading text-2xl font-bold text-emerald-800 mt-4 mb-6">Edit user #{{ $user->id }}</h2>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="bg-white rounded-xl shadow border border-gray-100 p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="firstname" class="block text-sm font-medium text-gray-700 mb-1">First name</label>
                <input id="firstname" name="firstname" type="text" value="{{ old('firstname', $user->firstname) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label for="lastname" class="block text-sm font-medium text-gray-700 mb-1">Last name</label>
                <input id="lastname" name="lastname" type="text" value="{{ old('lastname', $user->lastname) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Account status</label>
                <select id="is_active" name="is_active" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="1" @selected(old('is_active', $user->is_active ? '1' : '0') == '1')>Active</option>
                    <option value="0" @selected(old('is_active', $user->is_active ? '1' : '0') == '0')>Inactive</option>
                </select>
            </div>

            <button type="submit" class="bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-emerald-800">
                Save changes
            </button>
        </form>
    </div>
@endsection