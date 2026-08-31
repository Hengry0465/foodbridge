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

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Feature tests for donation release integration
 */
class DonationReleaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_cancellation_triggers_donation_release(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'cancelled', 'Test cancellation');

        $this->assertEquals('cancelled', $updatedPickup->status->code);
        $this->assertEquals('success', $updatedPickup->donation_release_status);
        $this->assertNotNull($updatedPickup->donation_released_at);
        
        // Check donation status
        $donation = Donation::find($pickup->donation_id);
        $this->assertEquals('released', $donation->status);
    }

    public function test_expiry_triggers_donation_release(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'expired_pickup');

        $this->assertEquals('expired_pickup', $updatedPickup->status->code);
        $this->assertEquals('success', $updatedPickup->donation_release_status);
        $this->assertNotNull($updatedPickup->donation_released_at);
        
        // Check donation status
        $donation = Donation::find($pickup->donation_id);
        $this->assertEquals('released', $donation->status);
    }

    public function test_completion_does_not_trigger_donation_release(): void
    {
        $pickup = $this->createConfirmedPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'completed');

        $this->assertEquals('completed', $updatedPickup->status->code);
        $this->assertNull($updatedPickup->donation_release_status);
        $this->assertNull($updatedPickup->donation_released_at);
        
        // Check donation status remains unchanged
        $donation = Donation::find($pickup->donation_id);
        $this->assertEquals('available', $donation->status);
    }

    public function test_duplicate_release_prevented(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        // First cancellation
        $updatedPickup = $service->transitionStatus($pickup, 'cancelled', 'First cancellation');
        $this->assertEquals('success', $updatedPickup->donation_release_status);

        // Try to release again (should not fail, just log)
        $gateway = app(\App\Services\DonationReleaseGateway::class);
        $result = $gateway->releaseDonation($updatedPickup);
        
        $this->assertTrue($result);
        $this->assertEquals('success', $updatedPickup->fresh()->donation_release_status);
    }

    private function createScheduledPickup(): Pickup
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id, 'status' => 'available']);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();

        return Pickup::factory()->create([
            'match_id' => $match->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    private function createConfirmedPickup(): Pickup
    {
        $pickup = $this->createScheduledPickup();
        $confirmedStatus = PickupStatus::where('code', 'confirmed')->first();
        $pickup->pickup_status_id = $confirmedStatus->id;
        $pickup->confirmed_at = now();
        $pickup->save();
        return $pickup->fresh();
    }
}
