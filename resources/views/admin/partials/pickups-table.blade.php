<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-heading text-xl font-bold text-emerald-800">Pickups</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Match</th>
                    <th class="px-4 py-3">Scheduled</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Completed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pickups as $pickup)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $pickup->id }}</td>
                        <td class="px-4 py-3">#{{ $pickup->match_id }}</td>
                        <td class="px-4 py-3">{{ $pickup->scheduled_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 capitalize">{{ $pickup->status->value }}</td>
                        <td class="px-4 py-3">{{ $pickup->completed_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No pickups found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $pickups->links() }}</div>
</section>
