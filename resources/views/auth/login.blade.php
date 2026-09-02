<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FoodBridge - Sign in</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body class="min-h-screen bg-slate-50 grid place-items-center p-6 text-slate-900">
<main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <p class="text-sm font-bold uppercase tracking-wider text-emerald-700">FoodBridge</p>
    <h1 class="mt-2 text-3xl font-bold">Recipient sign in</h1>
    <p class="mt-3 text-slate-600">Authentication is supplied by Module 1. Module 3 accepts only an authenticated Recipient session and never trusts a submitted recipient ID.</p>
    @if(app()->environment('local'))
        <form class="mt-6" method="POST" action="{{ route('demo.recipient.login') }}">
            @csrf
            <button class="w-full rounded-lg bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Use seeded recipient for local demonstration</button>
        </form>
    @endif
    <a class="mt-6 inline-block text-sm text-emerald-800 hover:underline" href="{{ route('welcome') }}">Back to FoodBridge</a>
</main>
</body>
</html>
