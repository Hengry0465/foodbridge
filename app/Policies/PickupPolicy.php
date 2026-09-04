<?php

namespace App\Policies;

use App\Models\Pickup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupPolicy - authorization rules for pickup operations
 * Prevents IDOR vulnerabilities by enforcing role-based access
 */
class PickupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the pickup
     */
    public function view(User $user, Pickup $pickup): bool
    {
        return $this->isRelatedUser($user, $pickup) || $user->is_admin;
    }

    /**
     * Determine if the user can create a pickup
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create pickups
        // Additional validation happens in the service layer
        return $user !== null;
    }

    /**
     * Determine if the user can update the pickup status
     */
    public function updateStatus(User $user, Pickup $pickup, string $targetStatus): bool
    {
        // Admins can update any pickup
        if ($user->role === 'admin') {
            return true;
        }

        $state = $pickup->getState();

        return match ($targetStatus) {
            'confirmed' => $this->canConfirm($user, $pickup, $state),
            'completed' => $this->canComplete($user, $pickup, $state),
            'cancelled' => $this->canCancel($user, $pickup, $state),
            'expired_pickup' => false, // Only system can expire
            default => false,
        };
    }

    /**
     * Determine if the user can view pickup history
     */
    public function viewHistory(User $user, ?int $requestedUserId = null): bool
    {
        // Admins can view all history
        if ($user->role === 'admin') {
            return true;
        }

        // Regular users can only view their own history
        // Reject if they try to view another user's history
        if ($requestedUserId !== null && $requestedUserId !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can confirm the pickup
     */
    private function canConfirm(User $user, Pickup $pickup, $state): bool
    {
        if (!$state->canConfirm()) {
            return false;
        }

        // Only the matched donor can confirm
        return $pickup->donor_id === $user->id;
    }

    /**
     * Check if user can complete the pickup
     */
    private function canComplete(User $user, Pickup $pickup, $state): bool
{
    return $pickup->donor_id === $user->id;
}

    /**
     * Check if user can cancel the pickup
     */
    private function canCancel(User $user, Pickup $pickup, $state): bool
    {
        if (!$state->canCancel()) {
            return false;
        }

        // Both donor and recipient can cancel
        return $pickup->donor_id === $user->id || $pickup->recipient_id === $user->id;
    }

    /**
     * Check if user is related to the pickup (donor or recipient)
     */
    private function isRelatedUser(User $user, Pickup $pickup): bool
    {
        return $pickup->donor_id === $user->id || $pickup->recipient_id === $user->id;
    }
}
