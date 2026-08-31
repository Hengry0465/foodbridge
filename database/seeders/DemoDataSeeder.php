<?php

namespace Database\Seeders;

use App\Enums\DonationStatus;
use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use App\Enums\FoodRequestStatus;
use App\Enums\MatchStatus;
use App\Enums\PickupStatus;
use App\Models\Donation;
use App\Models\DonationMatch;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $donors = collect([
            ['name' => 'Sunrise Bakery', 'username' => 'sunrise_bakery', 'email' => 'sunrise.bakery@foodbridge.test', 'region' => FoodRegion::KualaLumpur],
            ['name' => 'Green Mart Supermarket', 'username' => 'greenmart', 'email' => 'greenmart@foodbridge.test', 'region' => FoodRegion::Selangor],
            ['name' => 'Hotel Grand Plaza', 'username' => 'grandplaza', 'email' => 'grandplaza@foodbridge.test', 'region' => FoodRegion::Penang],
            ['name' => 'Community Kitchen KL', 'username' => 'ckl_donor', 'email' => 'ckl.donor@foodbridge.test', 'region' => FoodRegion::KualaLumpur],
            ['name' => 'Fresh Farms Wholesale', 'username' => 'freshfarms', 'email' => 'freshfarms@foodbridge.test', 'region' => FoodRegion::Johor],
        ])->map(fn (array $data) => User::factory()->donor()->create($data));

        $recipients = collect([
            ['name' => 'Hope Shelter NGO', 'username' => 'hope_shelter', 'email' => 'hope.shelter@foodbridge.test', 'region' => FoodRegion::KualaLumpur],
            ['name' => 'City Food Bank', 'username' => 'cityfoodbank', 'email' => 'cityfoodbank@foodbridge.test', 'region' => FoodRegion::Selangor],
            ['name' => 'St. Mary Community Kitchen', 'username' => 'stmary_kitchen', 'email' => 'stmary.kitchen@foodbridge.test', 'region' => FoodRegion::Penang],
            ['name' => 'Youth Outreach Centre', 'username' => 'youth_outreach', 'email' => 'youth.outreach@foodbridge.test', 'region' => FoodRegion::Sabah],
        ])->map(fn (array $data) => User::factory()->recipient()->create($data));

        $this->seedCompletedFlow($donors[0], $recipients[0], FoodCategory::Bakery, 45.5, 12);
        $this->seedCompletedFlow($donors[1], $recipients[1], FoodCategory::Produce, 120.0, 8);
        $this->seedCompletedFlow($donors[2], $recipients[2], FoodCategory::Prepared, 30.0, 5);
        $this->seedCompletedFlow($donors[3], $recipients[3], FoodCategory::Dairy, 18.75, 3);
        $this->seedCompletedFlow($donors[4], $recipients[0], FoodCategory::Produce, 85.0, 2);

        $this->createDonation($donors[0], FoodCategory::Bakery, 22.0, DonationStatus::Available, 6);
        $this->createDonation($donors[1], FoodCategory::Dairy, 15.5, DonationStatus::Available, 4);
        $this->createDonation($donors[2], FoodCategory::Prepared, 40.0, DonationStatus::Matched, 2);

        FoodRequest::query()->create([
            'user_id' => $recipients[1]->id,
            'donation_id' => null,
            'category' => FoodCategory::Produce,
            'region' => $recipients[1]->region,
            'status' => FoodRequestStatus::Pending,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        FoodRequest::query()->create([
            'user_id' => $recipients[2]->id,
            'donation_id' => null,
            'category' => FoodCategory::Bakery,
            'region' => $recipients[2]->region,
            'status' => FoodRequestStatus::Pending,
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(6),
        ]);

        FoodRequest::query()->create([
            'user_id' => $recipients[3]->id,
            'donation_id' => null,
            'category' => FoodCategory::Other,
            'region' => $recipients[3]->region,
            'status' => FoodRequestStatus::Rejected,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(9),
        ]);

        Donation::query()->create([
            'user_id' => $donors[0]->id,
            'category' => FoodCategory::Bakery,
            'quantity' => 12.0,
            'unit' => 'kg',
            'status' => DonationStatus::Expired,
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(13),
        ]);

        User::factory()->donor()->inactive()->create([
            'name' => 'Closed Cafe (Inactive)',
            'email' => 'inactive@foodbridge.test',
        ]);

        User::factory()->recipient()->create([
            'name' => 'New NGO Applicant',
            'email' => 'new.ngo@foodbridge.test',
        ]);
    }

    private function seedCompletedFlow(
        User $donor,
        User $recipient,
        FoodCategory $category,
        float $quantity,
        int $daysAgo,
    ): void {
        $createdAt = now()->subDays($daysAgo);

        $donation = Donation::query()->create([
            'user_id' => $donor->id,
            'category' => $category,
            'region' => $donor->region,
            'quantity' => $quantity,
            'unit' => 'kg',
            'status' => DonationStatus::Completed,
            'expires_at' => $createdAt->copy()->addDays(2),
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addDay(),
        ]);

        $request = FoodRequest::query()->create([
            'user_id' => $recipient->id,
            'donation_id' => $donation->id,
            'category' => $category,
            'region' => $recipient->region,
            'status' => FoodRequestStatus::Approved,
            'created_at' => $createdAt->copy()->addHours(3),
            'updated_at' => $createdAt->copy()->addHours(4),
        ]);

        $match = DonationMatch::query()->create([
            'donation_id' => $donation->id,
            'request_id' => $request->id,
            'status' => MatchStatus::Completed,
            'matched_at' => $createdAt->copy()->addHours(5),
            'created_at' => $createdAt->copy()->addHours(5),
            'updated_at' => $createdAt->copy()->addDay(),
        ]);

        Pickup::query()->create([
            'match_id' => $match->id,
            'scheduled_at' => $createdAt->copy()->addHours(8),
            'status' => PickupStatus::Completed,
            'completed_at' => $createdAt->copy()->addHours(9),
            'created_at' => $createdAt->copy()->addHours(6),
            'updated_at' => $createdAt->copy()->addHours(9),
        ]);
    }

    private function createDonation(
        User $donor,
        FoodCategory $category,
        float $quantity,
        DonationStatus $status,
        int $daysAgo,
    ): Donation {
        return Donation::query()->create([
            'user_id' => $donor->id,
            'category' => $category,
            'region' => $donor->region,
            'quantity' => $quantity,
            'unit' => 'kg',
            'status' => $status,
            'expires_at' => now()->addDays(2),
            'created_at' => now()->subDays($daysAgo),
            'updated_at' => now()->subDays($daysAgo),
        ]);
    }
}
