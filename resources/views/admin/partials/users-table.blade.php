<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h2 class="font-heading text-xl font-bold text-emerald-800">Platform Users</h2>
            <p class="text-gray-500 text-sm">View, edit, and manage user accounts.</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $user->id }}</td>
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3 capitalize">{{ $user->role }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 space-x-2">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-emerald-700 hover:underline text-xs">Edit</a>

                            @if ($user->is_active && $user->id !== auth()->id() && $user->role !== 'admin')
                                <form action="{{ route('admin.users.deactivate', $user) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Deactivate this user?')">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline text-xs">Deactivate</button>
                                </form>
                            @elseif (! $user->is_active)
                                <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-700 hover:underline text-xs">Activate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $users->links() }}</div>
</section>