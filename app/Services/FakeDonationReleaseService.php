<?php

namespace App\Services;

use App\Models\Pickup;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * FakeDonationReleaseService - demonstration implementation of DonationReleaseGateway
 * This is a local/fake implementation for demonstration purposes.
 * In production, this would be replaced with the actual Module 2 API integration.
 */
class FakeDonationReleaseService implements DonationReleaseGateway
{
    private int $timeout = 3; // seconds
    private int $retries = 1;

    public function releaseDonation(Pickup $pickup): bool
    {
        // Prevent duplicate releases
        if ($pickup->donation_release_status === 'success') {
            Log::info('Donation already released', ['pickup_id' => $pickup->id]);
            return true;
        }

        // Mark as pending before attempting release
        $pickup->donation_release_status = 'pending';
        $pickup->save();

        try {
            // Simulate HTTP call with timeout
            $result = $this->simulateReleaseCall($pickup);
            
            if ($result) {
                $pickup->donation_release_status = 'success';
                $pickup->donation_released_at = now();
                $pickup->save();

                // Update donation status locally
                $this->updateDonationStatus($pickup->donation_id);

                Log::info('Donation released successfully', [
                    'pickup_id' => $pickup->id,
                    'donation_id' => $pickup->donation_id
                ]);

                return true;
            } else {
                throw new \Exception('Release call failed');
            }
        } catch (\Exception $e) {
            // Retry once
            try {
                $result = $this->simulateReleaseCall($pickup);
                
                if ($result) {
                    $pickup->donation_release_status = 'success';
                    $pickup->donation_released_at = now();
                    $pickup->save();

                    $this->updateDonationStatus($pickup->donation_id);

                    Log::info('Donation released successfully on retry', [
                        'pickup_id' => $pickup->id,
                        'donation_id' => $pickup->donation_id
                    ]);

                    return true;
                }
            } catch (\Exception $retryException) {
                // Log failure but don't rollback the status transition
                $pickup->donation_release_status = 'failed';
                $pickup->save();

                Log::error('Donation release failed after retry', [
                    'pickup_id' => $pickup->id,
                    'donation_id' => $pickup->donation_id,
                    'error' => $retryException->getMessage()
                ]);
            }

            return false;
        }
    }

    /**
     * Simulate an HTTP call to Module 2's donation release API
     * In production, this would be an actual HTTP request with timeout
     */
    private function simulateReleaseCall(Pickup $pickup): bool
    {
        // Simulate network delay (max 3 seconds)
        $delay = min(rand(100, 500), $this->timeout * 1000);
        usleep($delay);

        // For demonstration, always succeed
        // In production, this would be: Http::timeout($this->timeout)->post(...)
        return true;
    }

    /**
     * Update donation status locally after successful release
     */
    private function updateDonationStatus(int $donationId): void
    {
        DB::transaction(function () use ($donationId) {
            $donation = Donation::find($donationId);
            if ($donation) {
                $donation->status = 'released';
                $donation->save();
            }
        });
    }
}
