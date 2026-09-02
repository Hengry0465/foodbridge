<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use App\Models\Donation;
use App\Models\FoodRequest;
use App\Models\MatchNotification;
use App\Services\AutoMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function index(Request $request): View
    {
        $recipient = $request->user();
        $availableDonations = Donation::with('donor')->where('status', 'available')->where('quantity_available', '>', 0)->where('expires_at', '>', now())->orderBy('expires_at')->get();
        $requests = FoodRequest::with('matches.donation.donor')->where('recipient_id', $recipient->id)->latest()->get();
        $notifications = MatchNotification::where('user_id', $recipient->id)->latest()->limit(5)->get();

        return view('recipient.dashboard', compact('recipient', 'availableDonations', 'requests', 'notifications'));
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
}
