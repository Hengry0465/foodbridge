<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodBridge - Edit Donation</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
        }

        .font-heading {
            font-family: 'Fraunces', serif;
        }
    </style>
</head>

<body class="w-full min-h-screen bg-gray-50">

    <header class="w-full border-b border-green-100 bg-white">
        <nav class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">FoodBridge</h1>
            <a href="{{ route('donor.dashboard') }}"
                class="px-5 py-2.5 rounded-full font-medium transition hover:opacity-90 bg-gray-900 text-white">
                Back to Dashboard
            </a>
        </nav>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10">
        <h2 class="font-heading font-bold text-2xl mb-6">Edit Donation</h2>

        <div class="bg-white rounded-2xl p-8 shadow-sm">
            <form method="POST" action="{{ route('donor.donations.update', $donation->id) }}"
                enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="food_name" class="block font-medium mb-2">Food Item</label>
                    <input id="food_name" name="food_name" type="text"
                        value="{{ old('food_name', $donation->food_name) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                    @error('food_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="donation_type" class="block font-medium mb-2">Food Type</label>
                    <select id="donation_type" name="donation_type"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                        @foreach (['cooked_food' => 'Cooked Food', 'fresh_produce' => 'Fresh Produce', 'packaged_goods' => 'Packaged Goods'] as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('donation_type', $donation->donation_type) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('donation_type')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block font-medium mb-2">Category</label>
                    <select id="category_id" name="category_id"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ old('category_id', $donation->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="quantity" class="block font-medium mb-2">Quantity</label>
                        <input id="quantity" name="quantity" type="number" step="0.01"
                            value="{{ old('quantity', $donation->quantity) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            required>
                        @error('quantity')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unit" class="block font-medium mb-2">Unit</label>
                        <input id="unit" name="unit" type="text" value="{{ old('unit', $donation->unit) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            required>
                        @error('unit')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="expiry_date" class="block font-medium mb-2">Expiry Date</label>
                    <input id="expiry_date" name="expiry_date" type="datetime-local"
                        value="{{ old('expiry_date', $donation->expiry_date?->format('Y-m-d\TH:i')) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('expiry_date')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="pickup_address" class="block font-medium mb-2">Pickup Address</label>
                    <input id="pickup_address" name="pickup_address" type="text"
                        value="{{ old('pickup_address', $donation->pickup_address) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                    @error('pickup_address')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    @if ($donation->image_url)
                        <p class="block font-medium mb-2">Current Photo</p>
                        <img src="{{ $donation->image_url }}" class="w-32 h-32 object-cover rounded-lg mb-3"
                            loading="lazy">
                    @endif

                    <label for="photo" class="block font-medium mb-2">
                        {{ $donation->image_url ? 'Replace Photo' : 'Upload Photo' }} (optional)
                    </label>
                    <input id="photo" name="photo" type="file" accept="image/*"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    @error('photo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex gap-3">
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-lg font-semibold transition hover:opacity-90 bg-green-600 text-white">
                        Save Changes
                    </button>
                    <a href="{{ route('donor.dashboard') }}"
                        class="flex-1 text-center py-2.5 rounded-lg font-semibold transition hover:bg-gray-50 border border-gray-300">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <footer class="w-full py-8 px-6 text-center bg-gray-900 text-gray-400 mt-14">
        <p>&copy; {{ date('Y') }} FoodBridge. Supporting SDG 2: Zero Hunger.</p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
