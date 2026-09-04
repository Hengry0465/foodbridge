<?php

namespace App\Services\States;

use App\Models\Pickup;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupStateFactory - creates appropriate state object based on pickup status
 */
class PickupStateFactory
{
    private static array $stateMap = [
        'scheduled' => ScheduledState::class,
        'confirmed' => ConfirmedState::class,
        'completed' => CompletedState::class,
        'cancelled' => CancelledState::class,
        'expired_pickup' => ExpiredPickupState::class,
    ];

    public static function create(Pickup $pickup): PickupState
    {
        $statusCode = $pickup->status?->code ?? 'scheduled';
        
        $stateClass = self::$stateMap[$statusCode] ?? ScheduledState::class;
        
        return new $stateClass();
    }

    public static function createByCode(string $code): PickupState
    {
        $stateClass = self::$stateMap[$code] ?? ScheduledState::class;
        
        return new $stateClass();
    }
}
