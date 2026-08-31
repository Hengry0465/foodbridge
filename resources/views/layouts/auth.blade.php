<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FoodBridge')</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .font-heading { font-family: 'Fraunces', serif; }
    </style>
</head>
<body class="min-h-screen bg-emerald-50">
    <div class="min-h-screen flex flex-col">
        <header class="px-6 py-5">
            <div class="max-w-md mx-auto flex items-center justify-between">
                <a href="{{ route('home') }}" class="font-heading text-2xl font-bold text-emerald-800">FoodBridge</a>
                @yield('header-link')
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 pb-12">
            <div class="w-full max-w-md">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-emerald-100 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
