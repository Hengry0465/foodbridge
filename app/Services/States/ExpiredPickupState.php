<?php

namespace App\Services\States;

use App\Models\Pickup;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * ExpiredPickupState - pickup expired due to lack of confirmation
 * No further transitions allowed
 */
class ExpiredPickupState implements PickupState
{
    public function getStatusCode(): string
    {
        return 'expired_pickup';
    }

    public function confirm(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from expired_pickup state');
    }

    public function complete(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from expired_pickup state');
    }

    public function cancel(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from expired_pickup state');
    }

    public function expire(Pickup $pickup): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from expired_pickup state');
    }

    public function canConfirm(): bool
    {
        return false;
    }

    public function canComplete(): bool
    {
        return false;
    }

    public function canCancel(): bool
    {
        return false;
    }

    public function canExpire(): bool
    {
        return false;
    }
}
