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
 * Feature tests for pickup expiry
 */
class PickupExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_expiry_command_expires_eligible_pickups(): void
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
            'scheduled_at' => now()->subHours(3), // More than 2 hours ago
        ]);

        $service = new PickupService();
        $expiredCount = $service->expireEligiblePickups();

        $this->assertEquals(1, $expiredCount);
        $this->assertDatabaseHas('pickups', [
            'match_id' => $match->id,
            'pickup_status_id' => PickupStatus::where('code', 'expired_pickup')->first()->id,
        ]);
    }

    public function test_confirmed_pickups_not_expired(): void
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

        $confirmedStatus = PickupStatus::where('code', 'confirmed')->first();
        Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $confirmedStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->subHours(3),
            'confirmed_at' => now()->subHours(2),
        ]);

        $service = new PickupService();
        $expiredCount = $service->expireEligiblePickups();

        $this->assertEquals(0, $expiredCount);
        $this->assertDatabaseHas('pickups', [
            'match_id' => $match->id,
            'pickup_status_id' => $confirmedStatus->id,
        ]);
    }

    public function test_recent_pickups_not_expired(): void
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
            'scheduled_at' => now()->addHours(1), // Future time
        ]);

        $service = new PickupService();
        $expiredCount = $service->expireEligiblePickups();

        $this->assertEquals(0, $expiredCount);
    }

    public function test_expiry_command_is_idempotent(): void
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
            'scheduled_at' => now()->subHours(3),
        ]);

        $service = new PickupService();
        
        // First run
        $expiredCount1 = $service->expireEligiblePickups();
        $this->assertEquals(1, $expiredCount1);

        // Second run (should not expire anything)
        $expiredCount2 = $service->expireEligiblePickups();
        $this->assertEquals(0, $expiredCount2);
    }

    public function test_pickup_is_eligible_for_expiry(): void
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
        $pickup = Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->subHours(3),
        ]);

        $this->assertTrue($pickup->isEligibleForExpiry());
    }

    public function test_pickup_not_eligible_when_confirmed(): void
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

        $confirmedStatus = PickupStatus::where('code', 'confirmed')->first();
        $pickup = Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $confirmedStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->subHours(3),
            'confirmed_at' => now()->subHours(2),
        ]);

        $this->assertFalse($pickup->isEligibleForExpiry());
    }
}
