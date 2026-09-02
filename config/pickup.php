<?php

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Pickup module configuration
 */
return [
    'slot_duration' => env('PICKUP_SLOT_DURATION', 60), // minutes
    'expiry_hours' => env('PICKUP_EXPIRY_HOURS', 2), // hours after scheduled time
];
