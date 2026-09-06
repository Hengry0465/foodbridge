<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodBridge - Pickup History</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .font-heading { font-family: 'Fraunces', serif; }
        .tag-scheduled { background: #dbeafe; color: #1e40af; }
        .tag-confirmed { background: #dcfce7; color: #166534; }
        .tag-completed { background: #e5e7eb; color: #374151; }
        .tag-cancelled { background: #f3f4f6; color: #6b7280; }
        .tag-expired_pickup { background: #fee2e2; color: #991b1b; }
    </style>
</head>

<body class="w-full min-h-screen bg-gray-50">

    <main class="max-w-7xl mx-auto px-6 py-10">

        <a href="{{ route($backRoute) }}"
            class="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 mb-6">
            ← Back to pickups
        </a>

        <h2 class="font-heading font-bold text-3xl mb-1">Pickup History</h2>
        <p class="text-gray-600 mb-8">A read-only record of all your past and current pickups.</p>

        <div class="mb-6">
            <input type="text" id="searchBox" placeholder="Search by food item or address..."
                class="w-full md:w-96 rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
        </div>

        <div class="bg-white rounded-2xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Food Item</th>
                        <th class="px-4 py-3">Donor</th>
                        <th class="px-4 py-3">Recipient</th>
                        <th class="px-4 py-3">Address</th>
                        <th class="px-4 py-3">Scheduled</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody id="historyTable">
                    @forelse ($pickups as $pickup)
                        <tr class="border-t border-gray-100"
                            data-search="{{ strtolower($pickup->donation->food_name.' '.$pickup->pickup_address) }}">
                            <td class="px-4 py-3">{{ $pickup->donation->food_name }}</td>
                            <td class="px-4 py-3">{{ $pickup->donor->name }}</td>
                            <td class="px-4 py-3">{{ $pickup->recipient->name }}</td>
                            <td class="px-4 py-3">{{ $pickup->pickup_address }}</td>
                            <td class="px-4 py-3">{{ $pickup->scheduled_at->format('d M Y, h:i A') }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="tag-{{ $pickup->status->code }} inline-block px-3 py-1 rounded-full text-xs font-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $pickup->status->code)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No pickup records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pickups->hasPages())
            <div class="mt-6">
                {{ $pickups->links() }}
            </div>
        @endif

    </main>

    <script>
        const searchBox = document.getElementById('searchBox');
        const rows = document.querySelectorAll('#historyTable tr[data-search]');

        searchBox.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            rows.forEach(function (row) {
                row.style.display = row.dataset.search.includes(term) ? '' : 'none';
            });
        });
    </script>

</body>

</html>