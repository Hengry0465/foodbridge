<?php

namespace App\Observers;

use App\Contracts\MatchObserver;
use App\Events\MatchOutcome;
use App\Models\MatchNotification;

class DonorNotifier implements MatchObserver
{
    public function update(MatchOutcome $event): void
    {
        foreach ($event->foodRequest->matches()->with('donation')->get()->unique('donation.donor_id') as $match) {
            MatchNotification::create([
                'user_id' => $match->donation->donor_id,
                'request_id' => $event->foodRequest->id,
                'type' => $event->type(),
                'message' => "Your {$match->donation->food_name} donation was matched for {$match->quantity_allocated} {$match->donation->unit}.",
            ]);
        }
    }
}
