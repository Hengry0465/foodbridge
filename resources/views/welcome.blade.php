<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FoodBridge</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  <style>
        body { font-family: 'DM Sans', sans-serif; }
        .font-heading { font-family: 'Fraunces', serif; }
    </style>
 </head>
 <body class="w-full bg-white">
  <header class="w-full">
   <nav class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
    <h1 class="font-heading text-2xl font-bold text-emerald-800">FoodBridge</h1>
    <a href="{{ route('login') }}" data-template-id="hero-cta" class="canva-button px-8 py-3.5 rounded-full font-semibold text-lg transition hover:scale-105 inline-block">
    Get Started</a>
   </nav>
   <section class="relative w-full overflow-hidden">
    <div class="absolute inset-0">
     <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=1600&q=80"
          class="w-full h-full object-cover" loading="lazy" alt="Fresh food ready for donation">
     <div class="absolute inset-0 bg-black/60"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-6 py-32 md:py-44 text-center text-white">
     <h2 class="font-heading text-4xl md:text-5xl font-bold mb-4">
      Connecting surplus food with the people who need it
     </h2>
     <p class="max-w-2xl mx-auto mb-8 text-lg opacity-90">
      FoodBridge links donors with recipient organisations to reduce food waste
      and fight hunger in our community — one donation at a time.
     </p>
        <a href="{{ route('login') }}" data-template-id="nav-cta" class="canva-button px-5 py-2.5 rounded-full font-medium transition hover:opacity-90 inline-block">
        Join FoodBridge</a>
    </div>
   </section>
  </header>

  <main>
   <!-- How It Works -->
   <section class="w-full py-20 px-6">
    <div class="max-w-7xl mx-auto text-center">
     <h2 class="font-heading text-3xl font-bold mb-14">How FoodBridge Works</h2>
     <div class="grid md:grid-cols-3 gap-10">
      <div class="flex flex-col items-center">
       <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
        <i data-lucide="package" class="w-7 h-7"></i>
       </div>
       <h3 class="font-semibold mb-2">1. Donor Lists Food</h3>
       <p class="text-gray-600">Restaurants, supermarkets, and households post surplus food with quantity and expiry details.</p>
      </div>
      <div class="flex flex-col items-center">
       <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
        <i data-lucide="shuffle" class="w-7 h-7"></i>
       </div>
       <h3 class="font-semibold mb-2">2. Recipient Requests</h3>
       <p class="text-gray-600">Recipient organisations browse available donations and submit a request. The system matches it automatically.</p>
      </div>
      <div class="flex flex-col items-center">
       <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
        <i data-lucide="calendar-check" class="w-7 h-7"></i>
       </div>
       <h3 class="font-semibold mb-2">3. Pickup Scheduled</h3>
       <p class="text-gray-600">Once matched, the Recipient schedules a pickup time and collects the food directly from the Donor.</p>
      </div>
     </div>
    </div>
   </section>

   <!-- Role Cards -->
   <section class="w-full py-20 px-6 bg-emerald-50">
    <div class="max-w-7xl mx-auto">
     <h2 class="font-heading text-3xl font-bold text-center mb-14">Who Uses FoodBridge</h2>
     <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">

      <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition">
       <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=800&q=80"
            class="w-full h-48 object-cover" loading="lazy" alt="Restaurant donating surplus food">
       <div class="p-6">
        <h3 class="font-semibold text-lg mb-2">Donors</h3>
        <p class="text-gray-600">
         Restaurants, supermarkets, and individuals with surplus food. List a
         donation in minutes and choose who collects it.
        </p>
       </div>
      </div>

      <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition">
       <img src="https://images.unsplash.com/photo-1593113646773-028c64a8f1b8?auto=format&fit=crop&w=800&q=80"
            class="w-full h-48 object-cover" loading="lazy" alt="Community kitchen receiving food donation">
       <div class="p-6">
        <h3 class="font-semibold text-lg mb-2">Recipients</h3>
        <p class="text-gray-600">
         NGOs, shelters, orphanages, and community kitchens. Browse available
         donations, request what you need, and schedule pickup.
        </p>
       </div>
      </div>

     </div>
    </div>
   </section>

   <!-- Impact Stats -->
   <section class="w-full py-20 px-6">
    <div class="max-w-7xl mx-auto text-center">
     <h2 class="font-heading text-3xl font-bold mb-14">Our Impact</h2>
     <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
      <div>
       <p class="font-heading text-4xl font-bold text-emerald-700">1,250+</p>
       <p class="mt-1 text-gray-600">Meals Redistributed</p>
      </div>
      <div>
       <p class="font-heading text-4xl font-bold text-emerald-700">180+</p>
       <p class="mt-1 text-gray-600">Active Donors</p>
      </div>
      <div>
       <p class="font-heading text-4xl font-bold text-emerald-700">45+</p>
       <p class="mt-1 text-gray-600">Partner Organisations</p>
      </div>
      <div>
       <p class="font-heading text-4xl font-bold text-emerald-700">3.2 tons</p>
       <p class="mt-1 text-gray-600">Food Waste Prevented</p>
      </div>
     </div>
    </div>
   </section>

   <!-- CTA -->
   <section class="w-full py-20 px-6 text-center bg-emerald-800 text-white">
    <h2 class="font-heading text-3xl font-bold mb-4">Ready to make a difference?</h2>
    <p class="mb-8 max-w-xl mx-auto opacity-90">
     Join FoodBridge today as a Donor or Recipient and help reduce food waste
     in your community.
    </p>
    <a href="{{ route('login') }}" class="inline-block bg-white text-emerald-800 px-8 py-3.5 rounded-full font-semibold text-lg transition hover:scale-105">Sign in to request food</a>
   </section>
  </main>

  <footer class="w-full py-8 px-6 text-center bg-gray-900 text-gray-400">
   <p>&copy; {{ date('Y') }} FoodBridge. Supporting SDG 2: Zero Hunger.</p>
  </footer>

  <script>
    lucide.createIcons();
  </script>
 </body>
</html>
