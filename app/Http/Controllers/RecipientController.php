<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Donation;
use App\Models\FoodRequest;
use App\Models\MatchNotification;
use App\Services\AutoMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Pickup;
use App\Models\MatchRecord;
use App\Services\PickupService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function index(Request $request): View
    {
        $recipient = $request->user();

        $availableDonations = Donation::with(['donor', 'category'])
            ->where('status', 'available')
            ->whereColumn('quantity_reserved', '<', 'quantity')
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();

        $requests = FoodRequest::with('matches.donation.donor')
            ->where('recipient_id', $recipient->id)
            ->latest()
            ->get();

        $notifications = MatchNotification::where('user_id', $recipient->id)
            ->latest()
            ->limit(5)
            ->get();

        $categories = \App\Models\FoodCategory::orderBy('name')->get();

        return view('recipient.dashboard', compact('recipient', 'availableDonations', 'requests', 'notifications', 'categories'));
    }

    public function store(StoreFoodRequest $request, AutoMatchingService $matcher): RedirectResponse
    {
        $validated = $request->validated();
        DB::transaction(function () use ($request, $validated, $matcher): void {
            $foodRequest = FoodRequest::create([
                'recipient_id' => $request->user()->id,
                'preferred_donation_id' => $validated['donation_id'] ?? null,
                'category' => $validated['category'],
                'quantity_requested' => $validated['quantity'],
                'preferred_pickup_at' => $validated['preferred_pickup_at'],
            ]);
            $matcher->match($foodRequest);
        }, 3);

        return to_route('recipient.dashboard')->with('success', 'Request submitted and matching completed.');
    }

    public function destroy(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        abort_unless($foodRequest->recipient_id === $request->user()->id, 403);
        abort_unless($foodRequest->status === 'pending', 409, 'Only unmatched requests may be withdrawn.');
        $foodRequest->update(['status' => 'withdrawn']);

        return to_route('recipient.dashboard')->with('success', 'Request withdrawn.');
    }

    public function pickups(PickupService $pickupService)
    {
        $recipient = Auth::user();
        $pickups = $pickupService->getPickupHistory($recipient);

        $unscheduledMatches = MatchRecord::whereHas('foodRequest', function ($q) {
            $q->where('recipient_id', Auth::id());
        })->whereDoesntHave('pickup')->with('donation.donor')->get();

        return view('recipient.pickups', compact('recipient', 'pickups', 'unscheduledMatches'));
    }

    public function schedulePickup(Request $request, PickupService $pickupService)
    {
        $validated = $request->validate([
            'match_id' => 'required|exists:matches,id',
            'scheduled_at' => 'required|date|after:now',
        ]);
        $pickupService->schedulePickup($validated, Auth::user());
        return redirect()->route('recipient.pickups')->with('success', 'Pickup scheduled.');
    }

    public function cancelPickup(Request $request, Pickup $pickup, PickupService $pickupService)
    {
        $this->authorize('updateStatus', [$pickup, 'cancelled']);
        $pickupService->transitionStatus($pickup, 'cancelled', $request->input('reason'));
        return redirect()->route('recipient.pickups')->with('success', 'Pickup cancelled.');
    }
}
