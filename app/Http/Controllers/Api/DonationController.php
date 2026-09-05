<?php
namespace App\Http\Controllers\Api;

use App\Factories\FoodDonationFactory;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    // GET /api/v1/donations
    public function index(Request $request): JsonResponse
    {
        $donations = Donation::with(['donor:id,firstname,lastname', 'category:id,name'])
            ->where('status', 'available')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->orderBy('expiry_date')
            ->paginate($request->integer('per_page', 15));

        return response()->json($donations);
    }

    // GET /api/v1/donations/{id}
    public function show(int $id): JsonResponse
    {
        $donation = Donation::with(['donor:id,firstname,lastname', 'category:id,name'])->findOrFail($id);

        return response()->json(['data' => $donation]);
    }

    // POST /api/v1/donations
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'food_name'      => 'required|string|max:120',
            'donation_type'  => 'required|in:cooked_food,fresh_produce,packaged_goods',
            'category_id'    => 'required|exists:food_categories,id',
            'quantity'       => 'required|numeric|min:0.01',
            'unit'           => 'required|string|max:20',
            'expiry_date'    => 'nullable|date',
            'pickup_address' => 'required|string|max:255',
        ]);

        $validated['donor_id'] = $request->user()->id;

        $donation = FoodDonationFactory::create($validated);

        return response()->json([
            'message' => 'Donation created successfully.',
            'data' => $donation,
        ], 201);
    }

    // POST /api/v1/donations/{id}/reserve
    public function reserve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'version'  => 'required|integer',
        ]);

        $donation = Donation::findOrFail($id);

        $available = $donation->quantity - $donation->quantity_reserved;
        if ($validated['quantity'] > $available) {
            return response()->json([
                'message' => 'Not enough quantity available.',
            ], 422);
        }

        // Optimistic locking: only update if the version still matches what the
        // client last read. If another request already reserved from this donation
        // in between, `version` will have moved on and this update touches 0 rows.
        $updatedRows = Donation::where('id', $donation->id)
            ->where('version', $validated['version'])
            ->update([
                'quantity_reserved' => $donation->quantity_reserved + $validated['quantity'],
                'version' => $donation->version + 1,
            ]);

        if ($updatedRows === 0) {
            return response()->json([
                'message' => 'This donation was just updated by someone else. Please refresh and try again.',
            ], 409);
        }

        $donation->refresh();

        if ($donation->quantity_reserved >= $donation->quantity && $donation->status !== 'reserved') {
            $donation->update(['status' => 'reserved']);
        }

        return response()->json([
            'message' => 'Donation reserved successfully.',
            'data' => $donation,
        ]);
    }
}