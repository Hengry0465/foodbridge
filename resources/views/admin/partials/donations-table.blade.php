<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-heading text-xl font-bold text-emerald-800">Donations</h2>
        <span class="text-sm text-gray-500">{{ $donations->total() }} records in database</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Donor</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Region</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($donations as $donation)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $donation->id }}</td>
                        <td class="px-4 py-3">{{ $donation->donor?->name ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ $donation->category->value }}</td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_', ' ', $donation->region->value)) }}</td>
                        <td class="px-4 py-3">{{ $donation->quantity }} {{ $donation->unit }}</td>
                        <td class="px-4 py-3 capitalize">{{ $donation->status->value }}</td>
                        <td class="px-4 py-3">{{ $donation->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No donations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $donations->links() }}</div>
</section>
