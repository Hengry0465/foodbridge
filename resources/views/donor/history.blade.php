<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FoodBridge - Donation History</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .font-heading { font-family: 'Fraunces', serif; }
    .tag-pending { background:#fef3c7; color:#92400e; }
    .tag-confirmed { background:#dbeafe; color:#1e40af; }
    .tag-cancelled { background:#f3f4f6; color:#6b7280; }
    .tag-expired { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body class="w-full min-h-screen bg-gray-50">

  <header class="w-full border-b border-green-100 bg-white">
    <nav class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <h1 class="font-heading text-2xl font-bold">FoodBridge</h1>
      <a href="{{ route('donor.dashboard') }}" class="px-5 py-2.5 rounded-full font-medium transition hover:opacity-90 bg-gray-900 text-white">
        Back to Dashboard
      </a>
    </nav>
  </header>

  <main class="max-w-5xl mx-auto px-6 py-10">
    <h2 class="font-heading font-bold text-2xl mb-2">Donation History</h2>
    <p class="text-gray-600 mb-8">See which organisations or individuals have claimed your donations.</p>

    @if($reservations->isEmpty())
      <div class="bg-white rounded-xl p-10 text-center shadow-sm">
        <i data-lucide="inbox" class="w-10 h-10 mx-auto text-gray-300 mb-3"></i>
        <p class="text-gray-500">No one has claimed your donations yet.</p>
      </div>
    @else
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-left">
          <thead class="bg-gray-100 text-gray-600 text-sm">
            <tr>
              <th class="px-5 py-3">Food Item</th>
              <th class="px-5 py-3">Recipient</th>
              <th class="px-5 py-3">Quantity Claimed</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($reservations as $reservation)
              <tr>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ $reservation->donation->food_name }}</p>
                  <p class="text-xs text-gray-400">{{ $reservation->donation->pickup_address }}</p>
                </td>
                <td class="px-5 py-4">
                  {{ $reservation->recipient->name ?? 'Unknown Recipient' }}
                </td>
                <td class="px-5 py-4">
                  {{ $reservation->quantity_reserved }} {{ $reservation->donation->unit }}
                </td>
                <td class="px-5 py-4">
                  <span class="tag-{{ $reservation->status }} inline-block px-3 py-1 rounded-full text-xs font-semibold">
                    {{ ucfirst($reservation->status) }}
                  </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500">
                  {{ $reservation->reserved_at->format('d M Y, h:i A') }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </main>

  <footer class="w-full py-8 px-6 text-center bg-gray-900 text-gray-400 mt-14">
    <p>&copy; {{ date('Y') }} FoodBridge. Supporting SDG 2: Zero Hunger.</p>
  </footer>

  <script>lucide.createIcons();</script>
</body>
</html>