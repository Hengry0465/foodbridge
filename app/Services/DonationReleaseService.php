<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\MatchRecord;
use App\Models\Pickup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationReleaseService implements DonationReleaseGateway
{
    public function releaseDonation(Pickup $pickup): bool
    {
        if ($pickup->donation_release_status === 'success') {
            Log::info('Donation already released', ['pickup_id' => $pickup->id]);
            return true;
        }

        $pickup->donation_release_status = 'pending';
        $pickup->save();

        try {
            DB::transaction(function () use ($pickup) {
                $match = MatchRecord::lockForUpdate()->findOrFail($pickup->match_id);
                $donation = Donation::lockForUpdate()->findOrFail($pickup->donation_id);

                $donation->quantity_reserved = max(0, $donation->quantity_reserved - $match->quantity_allocated);
                $donation->version++;

                if ($donation->status === 'reserved' && $donation->quantity_reserved < $donation->quantity) {
                    $donation->status = 'available';
                }
                $donation->save();

                $pickup->donation_release_status = 'success';
                $pickup->donation_released_at = now();
                $pickup->save();
            });

            Log::info('Donation released successfully', [
                'pickup_id' => $pickup->id,
                'donation_id' => $pickup->donation_id,
            ]);
            return true;
        } catch (\Throwable $e) {
            $pickup->donation_release_status = 'failed';
            $pickup->save();

            Log::error('Donation release failed', [
                'pickup_id' => $pickup->id,
                'donation_id' => $pickup->donation_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}