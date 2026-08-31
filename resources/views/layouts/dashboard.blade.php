<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — FoodBridge</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .font-heading { font-family: 'Fraunces', serif; }
    </style>
</head>
<body class="bg-emerald-50 min-h-screen">
    <div class="max-w-3xl mx-auto p-6">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm uppercase tracking-wide text-emerald-600 font-semibold">Signed in</p>
                    <h1 class="font-heading text-3xl font-bold text-emerald-800">{{ $title }}</h1>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('profile.edit') }}" class="text-emerald-700 font-medium hover:underline">Edit profile</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-100 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 mb-6">
                <p class="text-gray-700"><span class="font-medium">Name:</span> {{ auth()->user()->name }}</p>
                <p class="text-gray-700"><span class="font-medium">Email:</span> {{ auth()->user()->email }}</p>
                <p class="text-gray-700"><span class="font-medium">Role:</span> {{ auth()->user()->role->value }}</p>
                <p class="text-gray-700"><span class="font-medium">Status:</span> {{ auth()->user()->is_active ? 'Active' : 'Deactivated' }}</p>
            </div>

            <p class="text-gray-600 mb-6">{{ $message }}</p>

            <a href="{{ route('home') }}" class="text-emerald-700 font-medium hover:underline">← Back to home</a>
        </div>
    </div>
</body>
</html>
