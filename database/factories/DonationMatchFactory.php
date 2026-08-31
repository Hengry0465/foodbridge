<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\Donation;
use App\Models\DonationMatch;
use App\Models\FoodRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationMatch>
 */
class DonationMatchFactory extends Factory
{
    protected $model = DonationMatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'request_id' => FoodRequest::factory(),
            'status' => MatchStatus::Active,
            'matched_at' => now(),
        ];
    }
}
