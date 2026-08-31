<?php
// Author: [Your Name]
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\FoodCategory;
use App\Factories\FoodDonationFactory;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    // GET /donor/dashboard
    public function dashboard()
    {
        // TEMP: Member 1's Auth module isn't wired in yet, so we hardcode donor_id = 1.
        // Once login is ready, replace with: $donorId = Auth::id();
        $donorId = 1;

        $donations = Donation::with('category')
            ->where('donor_id', $donorId)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total_donations' => $donations->count(),
            'total_quantity'  => $donations->sum('quantity'),
            'active_listings' => $donations->whereIn('status', ['available', 'expiring_soon'])->count(),
        ];

        $categories = FoodCategory::orderBy('name')->get();

        return view('donor.dashboard', compact('donations', 'stats', 'categories'));
    }

    // POST /donor/donations  (Create)
    public function store(Request $request)
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $validated = $request->validate([
            'food_name'      => 'required|string|max:120',
            'donation_type'  => 'required|in:cooked_food,fresh_produce,packaged_goods',
            'category_id'    => 'required|exists:food_categories,id',
            'quantity'       => 'required|numeric|min:0.01',
            'unit'           => 'required|string|max:20',
            'expiry_date'    => 'nullable|date',
            'pickup_address' => 'required|string|max:255',
        ]);

        $validated['donor_id'] = $donorId;

        FoodDonationFactory::create($validated);

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation listed successfully!');
    }

    // GET /donor/donations/{id}/edit
    public function edit(int $id)
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $donation = Donation::where('donor_id', $donorId)->findOrFail($id);
        $categories = FoodCategory::orderBy('name')->get();

        return view('donor.edit', compact('donation', 'categories'));
    }

    // PUT /donor/donations/{id}  (Edit — Update)
    public function update(Request $request, int $id)
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $donation = Donation::where('donor_id', $donorId)->findOrFail($id);

        $validated = $request->validate([
            'food_name'      => 'required|string|max:120',
            'donation_type'  => 'required|in:cooked_food,fresh_produce,packaged_goods',
            'category_id'    => 'required|exists:food_categories,id',
            'quantity'       => 'required|numeric|min:0.01',
            'unit'           => 'required|string|max:20',
            'expiry_date'    => 'nullable|date',
            'pickup_address' => 'required|string|max:255',
        ]);

        // Increment version on every update (keeps optimistic locking consistent)
        $validated['version'] = $donation->version + 1;

        $donation->update($validated);

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation updated successfully!');
    }

    // DELETE /donor/donations/{id}  (Delete)
    public function destroy(int $id)
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $donation = Donation::where('donor_id', $donorId)->findOrFail($id);
        $donation->delete();

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation deleted successfully!');
    }
}