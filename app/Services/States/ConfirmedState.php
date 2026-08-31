<?php

namespace App\Services\States;

use App\Models\Pickup;
use App\Models\PickupStatus;
use App\Services\DonationReleaseGateway;
use Illuminate\Support\Facades\DB;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * ConfirmedState - pickup has been confirmed by donor
 * Allowed transitions: completed, cancelled
 */
class ConfirmedState implements PickupState
{
    public function getStatusCode(): string
    {
        return 'confirmed';
    }

    public function confirm(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from confirmed to confirmed');
    }

    public function complete(Pickup $pickup, ?string $reason = null): Pickup
    {
        if (!$this->canComplete()) {
            throw new \InvalidArgumentException('Cannot transition from confirmed to completed');
        }

        return DB::transaction(function () use ($pickup) {
            $completedStatus = PickupStatus::where('code', 'completed')->firstOrFail();
            
            $pickup->pickup_status_id = $completedStatus->id;
            $pickup->completed_at = now();
            $pickup->save();

            // Do NOT release donation on completion
            return $pickup->fresh();
        });
    }

    public function cancel(Pickup $pickup, ?string $reason = null): Pickup
    {
        if (!$this->canCancel()) {
            throw new \InvalidArgumentException('Cannot transition from confirmed to cancelled');
        }

        return DB::transaction(function () use ($pickup, $reason) {
            $cancelledStatus = PickupStatus::where('code', 'cancelled')->firstOrFail();
            
            $pickup->pickup_status_id = $cancelledStatus->id;
            $pickup->cancelled_at = now();
            $pickup->cancellation_reason = $reason;
            $pickup->save();

            // Trigger donation release
            app(DonationReleaseGateway::class)->releaseDonation($pickup);

            return $pickup->fresh();
        });
    }

    public function expire(Pickup $pickup): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from confirmed to expired_pickup');
    }

    public function canConfirm(): bool
    {
        return false;
    }

    public function canComplete(): bool
    {
        return true;
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canExpire(): bool
    {
        return false;
    }
}
