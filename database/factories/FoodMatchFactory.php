<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 */
class FoodMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donor_id' => User::factory(),
            'recipient_id' => User::factory(),
            'donation_id' => Donation::factory(),
            'request_id' => null,
            'status' => 'pending',
        ];
    }
}
