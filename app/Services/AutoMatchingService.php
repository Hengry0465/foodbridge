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
        private MatchPublisher $matchPublisher,
    ) {}

    public function match(FoodRequest $request): FoodRequest
    {
        $outcome = DB::transaction(function () use ($request): string {
            $remaining = $request->quantity_requested;

            $donations = Donation::query()
                ->whereHas('category', fn ($q) => $q->where('name', $request->category))
                ->where('status', 'available')
                ->whereColumn('quantity_reserved', '<', 'quantity')
                ->where('expiry_date', '>', now())
                ->when($request->preferred_donation_id, fn ($query) => $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$request->preferred_donation_id]))
                ->orderBy('expiry_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($donations as $donation) {
                if ($remaining <= 0) {
                    break;
                }
                $available = $donation->quantity - $donation->quantity_reserved;
                $allocated = min($remaining, $available);

                MatchRecord::create([
                    'request_id' => $request->id,
                    'donation_id' => $donation->id,
                    'quantity_allocated' => $allocated,
                ]);

                $donation->quantity_reserved += $allocated;
                $donation->version++;
                if ($donation->quantity_reserved >= $donation->quantity) {
                    $donation->status = 'reserved';
                }
                $donation->save();

                $remaining -= $allocated;
            }

            $matched = $request->quantity_requested - $remaining;
            $status = $matched == 0 ? 'pending' : ($remaining <= 0 ? 'matched' : 'partial');
            $request->update([
                'quantity_matched' => $matched,
                'status' => $status,
                'matched_at' => $matched > 0 ? now() : null,
            ]);

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