<?php
// Author: [Your Name]
namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\DonationReservation;
use App\Models\FoodCategory;
use App\Factories\FoodDonationFactory;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class DonorController extends Controller
{
    /// GET /donor/dashboard
    public function dashboard()
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        // Stats should reflect ALL donations, not just the latest 6
        $allDonations = Donation::where('donor_id', $donorId)->get();

        $stats = [
            'total_donations' => $allDonations->count(),
            'total_quantity'  => $allDonations->sum('quantity'),
            'active_listings' => $allDonations->whereIn('status', ['available', 'expiring_soon'])->count(),
        ];

        // Dashboard only shows the latest 6, ordered by most recently added
        $donations = Donation::with('category')
            ->where('donor_id', $donorId)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $categories = FoodCategory::orderBy('name')->get();

        return view('donor.dashboard', compact('donations', 'stats', 'categories'));
    }

    // GET /donor/donations/all
    public function allDonations()
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $donations = Donation::with('category')
            ->where('donor_id', $donorId)
            ->orderByDesc('created_at')
            ->paginate(12); // 分页显示，每页 12 笔

        return view('donor.all-donations', compact('donations'));
    }

    // POST /donor/donations
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
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // max 5MB
        ]);

        $validated['donor_id'] = $donorId;

        // Handle Cloudinary upload if a photo was submitted
        if ($request->hasFile('photo')) {
            $uploaded = Cloudinary::upload($request->file('photo')->getRealPath(), [
                'folder' => 'foodbridge/donations',
            ]);
            $validated['image_url'] = $uploaded->getSecurePath();
        }

        FoodDonationFactory::create($validated);

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation listed successfully!');
    }

    // GET /donor/donations/{id}/edit
    public function edit(int $id)
    {
        $donorId = 1;

        $donation = Donation::where('donor_id', $donorId)->findOrFail($id);
        $categories = FoodCategory::orderBy('name')->get();

        return view('donor.edit', compact('donation', 'categories'));
    }

    // PUT /donor/donations/{id}
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
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Only replace image_url if a new photo was uploaded
        if ($request->hasFile('photo')) {
            $uploaded = Cloudinary::upload($request->file('photo')->getRealPath(), [
                'folder' => 'foodbridge/donations',
            ]);
            $validated['image_url'] = $uploaded->get('secure_url');
        }

        $validated['version'] = $donation->version + 1;

        $donation->update($validated);

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation updated successfully!');
    }

    // DELETE /donor/donations/{id}
    public function destroy(int $id)
    {
        $donorId = 1;

        $donation = Donation::where('donor_id', $donorId)->findOrFail($id);
        $donation->delete();

        return redirect()->route('donor.dashboard')
            ->with('success', 'Donation deleted successfully!');
    }

    // GET /donor/donations/history
    public function history()
    {
        $donorId = 1; // TEMP — replace with Auth::id()

        $reservations = DonationReservation::whereHas('donation', function ($query) use ($donorId) {
            $query->where('donor_id', $donorId);
        })
            ->with(['donation', 'recipient'])
            ->orderByDesc('reserved_at')
            ->get();

        return view('donor.history', compact('reservations'));
    }
}
