<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-heading text-xl font-bold text-emerald-800">Requests</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Recipient</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Region</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $foodRequest)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $foodRequest->id }}</td>
                        <td class="px-4 py-3">{{ $foodRequest->recipient?->name ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ $foodRequest->category->value }}</td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_', ' ', $foodRequest->region->value)) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $foodRequest->status->value }}</td>
                        <td class="px-4 py-3">{{ $foodRequest->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $requests->links() }}</div>
</section>
