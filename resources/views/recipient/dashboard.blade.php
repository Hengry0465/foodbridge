<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>FoodBridge — Request & Auto Matching</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'DM Sans', sans-serif
        }

        .font-heading {
            font-family: 'Fraunces', serif
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">
    <header class="bg-emerald-900 text-white">
        <nav class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <span class="font-heading text-2xl font-bold">FoodBridge</span>
            <div class="flex items-center gap-5">
                <a href="{{ route('profile.edit') }}"
                    class="text-sm px-3 py-2 rounded-lg border border-emerald-600 hover:bg-emerald-800">Profile</a>
                <a href="{{ route('profile.password.form') }}"
                    class="text-sm px-3 py-2 rounded-lg border border-emerald-600 hover:bg-emerald-800">Password</a>
                <a href="{{ route('recipient.pickups') }}"
                    class="text-sm px-3 py-2 rounded-lg border border-emerald-600 hover:bg-emerald-800">Pickups</a>
                <div class="text-right">
                    <p class="font-medium">{{ $recipient->name }}</p>
                    <p class="text-xs text-emerald-200">Team Member 3 · Request & Auto Matching</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-emerald-600 px-3 py-2 text-sm hover:bg-emerald-800">Sign
                        out</button>
                </form>
            </div>
        </nav>
    </header>
    <main class="max-w-7xl mx-auto px-6 py-10 space-y-10">
        @if (session('success'))
            <div class="rounded-xl bg-emerald-100 border border-emerald-200 p-4 text-emerald-900">
                {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-800"><strong>Please fix:</strong>
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <section class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h1 class="font-heading text-3xl font-bold">Request food</h1>
                <p class="text-slate-600 mt-1 mb-6">Requests match by category, quantity, nearest expiry, then
                    first-come-first-served.</p>
                <form method="POST" action="{{ route('recipient.requests.store') }}" class="grid md:grid-cols-2 gap-4">
                    @csrf<label><span class="text-sm font-medium">Food category</span><select name="category" required
                            class="mt-1 w-full rounded-lg border p-3">
                            @foreach ($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select></label><label><span class="text-sm font-medium">Quantity (portions)</span><input
                            name="quantity" type="number" min="1" max="10000" required
                            value="{{ old('quantity', 10) }}" class="mt-1 w-full rounded-lg border p-3"></label><label
                        class="md:col-span-2"><span class="text-sm font-medium">Preferred pickup date and
                            time</span><input name="preferred_pickup_at" type="datetime-local" required
                            min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                            value="{{ old('preferred_pickup_at', now()->addHours(2)->format('Y-m-d\TH:i')) }}"
                            class="mt-1 w-full rounded-lg border p-3"></label><button
                        class="md:col-span-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg py-3 font-semibold">Submit
                        general request & run auto-match</button></form>
            </div>
            <aside class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="font-heading text-xl font-bold mb-4">Latest notifications</h2>
                <div class="space-y-3">
                    @forelse($notifications as $notification)
                        <div class="rounded-lg bg-slate-50 p-3"><span
                                class="text-xs uppercase font-bold text-emerald-700">{{ $notification->type }}</span>
                            <p class="text-sm mt-1">{{ $notification->message }}</p>
                    </div>@empty<p class="text-slate-500 text-sm">Notifications appear after matching.</p>
                    @endforelse
                </div>
            </aside>
        </section>
        <section>
            <h2 class="font-heading text-2xl font-bold mb-1">Available donations</h2>
            <p class="text-sm text-slate-600 mb-4">Set the quantity and pickup time on a donation, then submit it
                directly. The history and notifications refresh after matching.</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($availableDonations as $donation)
                    <article class="bg-white rounded-xl border p-5">
                        <div class="flex justify-between gap-3">
                            <h3 class="font-bold">{{ $donation->food_name }}</h3>
                            <span
                                class="text-xs bg-emerald-100 text-emerald-800 rounded-full px-2 py-1 h-fit">{{ $donation->category->name }}</span>
                        </div>
                        <p class="text-sm text-slate-600 mt-3">Donor: {{ $donation->donor->name }} ·
                            {{ $donation->quantity - $donation->quantity_reserved }} {{ $donation->unit }}</p>
                        <p class="text-sm text-amber-700 mt-1">Expires {{ $donation->expiry_date->diffForHumans() }}
                        </p>
                        <p class="text-xs text-slate-500 mt-2">{{ $donation->pickup_address }}</p>
                        <form method="POST" action="{{ route('recipient.requests.store') }}" class="mt-4 space-y-3">
                            @csrf<input type="hidden" name="donation_id" value="{{ $donation->id }}"><input
                                type="hidden" name="category" value="{{ $donation->category->name }}"><label
                                class="block"><span class="text-xs font-medium">Quantity</span>@php $availableQty = $donation->quantity - $donation->quantity_reserved; @endphp
                                <input name="quantity" type="number" min="1" max="{{ $availableQty }}" required
                                    value="{{ min(10, $availableQty) }}"
                                    class="mt-1 w-full rounded-lg border p-2"></label><label class="block"><span
                                    class="text-xs font-medium">Preferred pickup date and time</span><input
                                    name="preferred_pickup_at" type="datetime-local" required
                                    min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                    value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}"
                                    class="mt-1 w-full rounded-lg border p-2"></label><button
                                class="w-full rounded-lg bg-emerald-700 text-white py-2 font-semibold hover:bg-emerald-800">Request
                                this donation</button></form>
                </article>@empty<p class="text-slate-500">No donations currently available.</p>
                @endforelse
            </div>
        </section>
        <section>
            <h2 class="font-heading text-2xl font-bold mb-4">My request history</h2>
            <div class="overflow-x-auto bg-white rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-left">
                        <tr>
                            <th class="p-4">Submitted</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Requested</th>
                            <th class="p-4">Matched allocation</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $foodRequest)
                            <tr class="border-t align-top">
                                <td class="p-4">{{ $foodRequest->created_at->format('d M Y, H:i') }}</td>
                                <td class="p-4">{{ $foodRequest->category }}</td>
                                <td class="p-4">{{ $foodRequest->quantity_requested }}</td>
                                <td class="p-4"><strong>{{ $foodRequest->quantity_matched }} portions</strong>
                                    @foreach ($foodRequest->matches as $match)
                                        <p class="mt-1 text-xs text-slate-600">{{ $match->quantity_allocated }} from
                                            {{ $match->donation->food_name }} · donor
                                            {{ $match->donation->donor->name }}</p>
                                        @endforeach @if ($foodRequest->matches->isEmpty())
                                            <p class="mt-1 text-xs text-slate-400">No donor allocated yet</p>
                                        @endif
                                </td>
                                <td class="p-4"><span
                                        class="rounded-full px-2 py-1 text-xs font-bold {{ $foodRequest->status === 'matched' ? 'bg-emerald-100 text-emerald-800' : ($foodRequest->status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100') }}">{{ ucfirst($foodRequest->status) }}</span>
                                </td>
                                <td class="p-4">
                                    @if ($foodRequest->status === 'pending')
                                        <form method="POST"
                                            action="{{ route('recipient.requests.destroy', $foodRequest) }}">@csrf
                                            @method('DELETE')<button
                                            class="text-red-700 hover:underline">Withdraw</button></form>@else<span
                                            class="text-slate-400">Locked after match</span>
                                    @endif
                                </td>
                        </tr>@empty<tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">No requests submitted yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>

</html>
