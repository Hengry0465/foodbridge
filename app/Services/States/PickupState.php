<?php

namespace App\Services\States;

use App\Models\Pickup;
use App\Models\PickupStatus;
use Illuminate\Support\Facades\DB;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupState interface for State pattern
 */
interface PickupState
{
    /**
     * Get the status code for this state
     */
    public function getStatusCode(): string;

    /**
     * Transition to confirmed state
     */
    public function confirm(Pickup $pickup, ?string $reason = null): Pickup;

    /**
     * Transition to completed state
     */
    public function complete(Pickup $pickup, ?string $reason = null): Pickup;

    /**
     * Transition to cancelled state
     */
    public function cancel(Pickup $pickup, ?string $reason = null): Pickup;

    /**
     * Transition to expired_pickup state
     */
    public function expire(Pickup $pickup): Pickup;

    /**
     * Check if transition to confirmed is allowed
     */
    public function canConfirm(): bool;

    /**
     * Check if transition to completed is allowed
     */
    public function canComplete(): bool;

    /**
     * Check if transition to cancelled is allowed
     */
    public function canCancel(): bool;

    /**
     * Check if transition to expired_pickup is allowed
     */
    public function canExpire(): bool;
}
