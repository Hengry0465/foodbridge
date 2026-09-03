<?php

namespace App\Observers;

use App\Contracts\MatchObserver;
use App\Events\MatchOutcome;
use App\Models\MatchNotification;

class RecipientNotifier implements MatchObserver
{
    public function update(MatchOutcome $event): void
    {
        $request = $event->foodRequest;
        $label = match ($event->type()) {
            'matched' => 'Match succeeded',
            'partial' => 'Partial match found',
            default => 'No match yet',
        };
        MatchNotification::create([
            'user_id' => $request->recipient_id,
            'request_id' => $request->id,
            'type' => $event->type(),
            'message' => "$label: {$request->quantity_matched} of {$request->quantity_requested} portions allocated.",
        ]);
    }
}
