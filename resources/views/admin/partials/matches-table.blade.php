<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-heading text-xl font-bold text-emerald-800">Matches</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Donation</th>
                    <th class="px-4 py-3">Request</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Matched At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matches as $match)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $match->id }}</td>
                        <td class="px-4 py-3">{{ $match->donation->food_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $match->foodRequest->category ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ $match->status }}</td>
                        <td class="px-4 py-3">{{ $match->created_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No matches found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $matches->links() }}</div>
</section>