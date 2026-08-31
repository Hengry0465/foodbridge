<?php

namespace App\Services;

use App\Models\Pickup;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * DonationReleaseGateway interface for Module 2 integration
 * This interface allows for dependency injection and easy replacement
 * with the actual Module 2 implementation when available.
 */
interface DonationReleaseGateway
{
    /**
     * Release the donation associated with a cancelled or expired pickup
     * 
     * @param Pickup $pickup The pickup that triggered the release
     * @return bool True if release was successful, false otherwise
     */
    public function releaseDonation(Pickup $pickup): bool;
}
