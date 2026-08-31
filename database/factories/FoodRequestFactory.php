<?php

namespace Database\Factories;

use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use App\Enums\FoodRequestStatus;
use App\Models\FoodRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodRequest>
 */
class FoodRequestFactory extends Factory
{
    protected $model = FoodRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->recipient(),
            'donation_id' => null,
            'category' => fake()->randomElement(FoodCategory::cases()),
            'region' => fake()->randomElement(FoodRegion::cases()),
            'status' => FoodRequestStatus::Pending,
        ];
    }
}
