<?php

namespace App\Services\States;

use App\Models\Pickup;
use App\Models\PickupStatus;
use App\Services\DonationReleaseGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * ScheduledState - pickup has been scheduled by recipient
 * Allowed transitions: confirmed, cancelled, expired_pickup
 */
class ScheduledState implements PickupState
{
    public function getStatusCode(): string
    {
        return 'scheduled';
    }

    public function confirm(Pickup $pickup, ?string $reason = null): Pickup
    {
        if (!$this->canConfirm()) {
            throw new \InvalidArgumentException('Cannot transition from scheduled to confirmed');
        }

        return DB::transaction(function () use ($pickup) {
            $confirmedStatus = PickupStatus::where('code', 'confirmed')->firstOrFail();
            
            $pickup->pickup_status_id = $confirmedStatus->id;
            $pickup->confirmed_at = now();
            $pickup->save();

            return $pickup->fresh();
        });
    }

    public function complete(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition directly from scheduled to completed');
    }

    public function cancel(Pickup $pickup, ?string $reason = null): Pickup
    {
        if (!$this->canCancel()) {
            throw new \InvalidArgumentException('Cannot transition from scheduled to cancelled');
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
        if (!$this->canExpire()) {
            throw new \InvalidArgumentException('Cannot transition from scheduled to expired_pickup');
        }

        return DB::transaction(function () use ($pickup) {
            $expiredStatus = PickupStatus::where('code', 'expired_pickup')->firstOrFail();
            
            $pickup->pickup_status_id = $expiredStatus->id;
            $pickup->expired_at = now();
            $pickup->save();

            // Trigger donation release
            app(DonationReleaseGateway::class)->releaseDonation($pickup);

            return $pickup->fresh();
        });
    }

    public function canConfirm(): bool
    {
        return true;
    }

    public function canComplete(): bool
    {
        return false;
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canExpire(): bool
    {
        return true;
    }
}
