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
 * Feature tests for pickup status transitions
 */
class PickupStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_scheduled_to_confirmed_transition(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'confirmed');

        $this->assertEquals('confirmed', $updatedPickup->status->code);
        $this->assertNotNull($updatedPickup->confirmed_at);
    }

    public function test_scheduled_to_cancelled_transition(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'cancelled', 'Changed mind');

        $this->assertEquals('cancelled', $updatedPickup->status->code);
        $this->assertNotNull($updatedPickup->cancelled_at);
        $this->assertEquals('Changed mind', $updatedPickup->cancellation_reason);
    }

    public function test_scheduled_to_expired_pickup_transition(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'expired_pickup');

        $this->assertEquals('expired_pickup', $updatedPickup->status->code);
        $this->assertNotNull($updatedPickup->expired_at);
    }

    public function test_confirmed_to_completed_transition(): void
    {
        $pickup = $this->createConfirmedPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'completed');

        $this->assertEquals('completed', $updatedPickup->status->code);
        $this->assertNotNull($updatedPickup->completed_at);
    }

    public function test_confirmed_to_cancelled_transition(): void
    {
        $pickup = $this->createConfirmedPickup();
        $service = new PickupService();

        $updatedPickup = $service->transitionStatus($pickup, 'cancelled', 'Emergency');

        $this->assertEquals('cancelled', $updatedPickup->status->code);
        $this->assertNotNull($updatedPickup->cancelled_at);
    }

    public function test_completed_no_further_transitions(): void
    {
        $pickup = $this->createCompletedPickup();
        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transitionStatus($pickup, 'cancelled');
    }

    public function test_cancelled_no_further_transitions(): void
    {
        $pickup = $this->createCancelledPickup();
        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transitionStatus($pickup, 'confirmed');
    }

    public function test_expired_pickup_no_further_transitions(): void
    {
        $pickup = $this->createExpiredPickup();
        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transitionStatus($pickup, 'confirmed');
    }

    public function test_invalid_scheduled_to_completed_transition(): void
    {
        $pickup = $this->createScheduledPickup();
        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transitionStatus($pickup, 'completed');
    }

    public function test_invalid_confirmed_to_expired_transition(): void
    {
        $pickup = $this->createConfirmedPickup();
        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->transitionStatus($pickup, 'expired_pickup');
    }

    private function createScheduledPickup(): Pickup
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

    private function createCompletedPickup(): Pickup
    {
        $pickup = $this->createConfirmedPickup();
        $completedStatus = PickupStatus::where('code', 'completed')->first();
        $pickup->pickup_status_id = $completedStatus->id;
        $pickup->completed_at = now();
        $pickup->save();
        return $pickup->fresh();
    }

    private function createCancelledPickup(): Pickup
    {
        $pickup = $this->createScheduledPickup();
        $cancelledStatus = PickupStatus::where('code', 'cancelled')->first();
        $pickup->pickup_status_id = $cancelledStatus->id;
        $pickup->cancelled_at = now();
        $pickup->save();
        return $pickup->fresh();
    }

    private function createExpiredPickup(): Pickup
    {
        $pickup = $this->createScheduledPickup();
        $expiredStatus = PickupStatus::where('code', 'expired_pickup')->first();
        $pickup->pickup_status_id = $expiredStatus->id;
        $pickup->expired_at = now();
        $pickup->save();
        return $pickup->fresh();
    }
}
