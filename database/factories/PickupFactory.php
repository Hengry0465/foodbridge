<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Donation;
use App\Models\FoodMatch;
use App\Models\PickupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 */
class PickupFactory extends Factory
{
    public function definition(): array
    {
        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        
        return [
            'match_id' => FoodMatch::factory(),
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => User::factory(),
            'recipient_id' => User::factory(),
            'donation_id' => Donation::factory(),
            'pickup_address' => fake()->address(),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+7 days'),
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'expired_at' => null,
            'cancellation_reason' => null,
            'donation_release_status' => null,
            'donation_released_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
