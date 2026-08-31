<?php

namespace Database\Factories;

use App\Enums\DonationStatus;
use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->donor(),
            'category' => fake()->randomElement(FoodCategory::cases()),
            'region' => fake()->randomElement(FoodRegion::cases()),
            'quantity' => fake()->randomFloat(2, 1, 50),
            'unit' => 'kg',
            'status' => DonationStatus::Available,
            'expires_at' => now()->addDays(fake()->numberBetween(1, 7)),
        ];
    }
}
