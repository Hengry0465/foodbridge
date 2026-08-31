<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Donation;
use App\Models\FoodMatch;
use App\Models\Pickup;
use App\Models\PickupStatus;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Feature tests for pickup history
 */
class PickupHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_recipient_sees_own_pickups_as_recipient(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(2),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($recipient);

        $this->assertCount(1, $history);
        $this->assertEquals($recipient->id, $history->first()->recipient_id);
    }

    public function test_donor_sees_own_pickups_as_donor(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(2),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($donor);

        $this->assertCount(1, $history);
        $this->assertEquals($donor->id, $history->first()->donor_id);
    }

    public function test_admin_can_view_all_pickups(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(2),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($admin);

        $this->assertCount(1, $history);
    }

    public function test_history_filters_by_status(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        $completedStatus = PickupStatus::where('code', 'completed')->first();

        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(2),
        ]);

        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $completedStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(4),
            'completed_at' => now(),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($recipient, ['status' => 'completed']);

        $this->assertCount(1, $history);
        $this->assertEquals('completed', $history->first()->status->code);
    }

    public function test_history_filters_by_date_range(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();

        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addDays(5),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($recipient, [
            'date_from' => now()->addDays(2)->toDateString(),
            'date_to' => now()->addDays(6)->toDateString(),
        ]);

        $this->assertCount(1, $history);
    }

    public function test_history_is_paginated(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();

        for ($i = 0; $i < 25; $i++) {
            $match = FoodMatch::factory()->create([
                'donor_id' => $donor->id,
                'recipient_id' => $recipient->id,
                'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
                'status' => 'successful',
            ]);

            Pickup::factory()->create([
                'match_id' => $match->id,
                'pickup_status_id' => $scheduledStatus->id,
                'donor_id' => $donor->id,
                'recipient_id' => $recipient->id,
                'donation_id' => $match->donation_id,
                'scheduled_at' => now()->addHours($i + 1),
            ]);
        }

        $service = new PickupService();
        $history = $service->getPickupHistory($recipient, ['per_page' => 10]);

        $this->assertCount(10, $history);
        $this->assertEquals(25, $history->total());
        $this->assertEquals(3, $history->lastPage());
    }

    public function test_history_sorted_newest_first(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();

        $match1 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
            'status' => 'successful',
        ]);

        $match2 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
            'status' => 'successful',
        ]);

        $pickup1 = Pickup::factory()->create([
            'match_id' => $match1->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $match1->donation_id,
            'scheduled_at' => now()->addHours(2),
            'created_at' => now()->subHour(),
        ]);

        $pickup2 = Pickup::factory()->create([
            'match_id' => $match2->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $match2->donation_id,
            'scheduled_at' => now()->addHours(4),
            'created_at' => now(),
        ]);

        $service = new PickupService();
        $history = $service->getPickupHistory($recipient);

        $this->assertEquals($pickup2->id, $history->first()->id);
    }
}
