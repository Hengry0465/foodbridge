<?php

namespace Database\Factories;

use App\Enums\PickupStatus;
use App\Models\DonationMatch;
use App\Models\Pickup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pickup>
 */
class PickupFactory extends Factory
{
    protected $model = Pickup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_id' => DonationMatch::factory(),
            'scheduled_at' => now()->addDay(),
            'status' => PickupStatus::Scheduled,
            'completed_at' => null,
        ];
    }
}
