<?php

namespace App\Services\States;

use App\Models\Pickup;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * CancelledState - pickup was cancelled
 * No further transitions allowed
 */
class CancelledState implements PickupState
{
    public function getStatusCode(): string
    {
        return 'cancelled';
    }

    public function confirm(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from cancelled state');
    }

    public function complete(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from cancelled state');
    }

    public function cancel(Pickup $pickup, ?string $reason = null): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from cancelled state');
    }

    public function expire(Pickup $pickup): Pickup
    {
        throw new \InvalidArgumentException('Cannot transition from cancelled state');
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
