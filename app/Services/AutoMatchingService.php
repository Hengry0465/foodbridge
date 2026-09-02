<?php

namespace App\Services;

use App\Events\MatchFailed;
use App\Events\MatchSucceeded;
use App\Events\PartialMatch;
use App\Models\Donation;
use App\Models\FoodRequest;
use App\Models\MatchRecord;
use Illuminate\Support\Facades\DB;

class AutoMatchingService
{
    public function __construct(
        private ModuleApiClient $moduleApiClient,
        private MatchPublisher $matchPublisher,
    ) {}

    public function match(FoodRequest $request): FoodRequest
    {
        abort_unless($this->moduleApiClient->verifyRecipient($request->recipient_id), 422, 'Recipient identity could not be verified.');
        $remoteDonations = $this->moduleApiClient->availableDonations($request->category);

        $outcome = DB::transaction(function () use ($request, $remoteDonations): string {
            $remaining = $request->quantity_requested;
            $donations = Donation::query()
                ->where('category', $request->category)
                ->where('status', 'available')
                ->where('quantity_available', '>', 0)
                ->where('expires_at', '>', now())
                ->when(is_array($remoteDonations), function ($query) use ($remoteDonations): void {
                    $query->whereIn('id', collect($remoteDonations)->pluck('id')->filter()->all());
                })
                ->when($request->preferred_donation_id, fn ($query) => $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$request->preferred_donation_id]))
                ->orderBy('expires_at')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($donations as $donation) {
                if ($remaining === 0) {
                    break;
                }
                $allocated = min($remaining, $donation->quantity_available);
                MatchRecord::create(['request_id' => $request->id, 'donation_id' => $donation->id, 'quantity_allocated' => $allocated]);
                $donation->quantity_available -= $allocated;
                $donation->version++;
                if ($donation->quantity_available === 0) {
                    $donation->status = 'reserved';
                }
                $donation->save();
                $remaining -= $allocated;
            }

            $matched = $request->quantity_requested - $remaining;
            $status = $matched === 0 ? 'pending' : ($remaining === 0 ? 'matched' : 'partial');
            $request->update(['quantity_matched' => $matched, 'status' => $status, 'matched_at' => $matched > 0 ? now() : null]);

            return $status;
        }, 3);

        $request->refresh()->load('matches.donation.donor');
        $event = match ($outcome) {
            'matched' => new MatchSucceeded($request),
            'partial' => new PartialMatch($request),
            default => new MatchFailed($request),
        };
        $this->matchPublisher->notify($event);

        return $request;
    }
}
