<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Donation;
use App\Models\FoodMatch;
use App\Models\Pickup;
use App\Models\PickupStatus;
use App\Policies\PickupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Feature tests for pickup authorization
 */
class PickupAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_recipient_can_view_own_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->view($pickup->recipient, $pickup));
    }

    public function test_donor_can_view_own_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->view($pickup->donor, $pickup));
    }

    public function test_admin_can_view_any_pickup(): void
    {
        $pickup = $this->createPickup();
        $admin = User::factory()->create(['is_admin' => true]);
        $policy = new PickupPolicy();

        $this->assertTrue($policy->view($admin, $pickup));
    }

    public function test_unrelated_user_cannot_view_pickup(): void
    {
        $pickup = $this->createPickup();
        $unrelatedUser = User::factory()->create();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->view($unrelatedUser, $pickup));
    }

    public function test_donor_can_confirm_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->updateStatus($pickup->donor, $pickup, 'confirmed'));
    }

    public function test_recipient_cannot_confirm_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->updateStatus($pickup->recipient, $pickup, 'confirmed'));
    }

    public function test_donor_can_complete_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->updateStatus($pickup->donor, $pickup, 'completed'));
    }

    public function test_recipient_cannot_complete_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->updateStatus($pickup->recipient, $pickup, 'completed'));
    }

    public function test_both_donor_and_recipient_can_cancel_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->updateStatus($pickup->donor, $pickup, 'cancelled'));
        $this->assertTrue($policy->updateStatus($pickup->recipient, $pickup, 'cancelled'));
    }

    public function test_admin_can_update_any_pickup_status(): void
    {
        $pickup = $this->createPickup();
        $admin = User::factory()->create(['is_admin' => true]);
        $policy = new PickupPolicy();

        $this->assertTrue($policy->updateStatus($admin, $pickup, 'confirmed'));
        $this->assertTrue($policy->updateStatus($admin, $pickup, 'completed'));
        $this->assertTrue($policy->updateStatus($admin, $pickup, 'cancelled'));
    }

    public function test_unrelated_user_cannot_update_pickup_status(): void
    {
        $pickup = $this->createPickup();
        $unrelatedUser = User::factory()->create();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->updateStatus($unrelatedUser, $pickup, 'confirmed'));
        $this->assertFalse($policy->updateStatus($unrelatedUser, $pickup, 'cancelled'));
    }

    public function test_no_user_can_expire_pickup(): void
    {
        $pickup = $this->createPickup();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->updateStatus($pickup->donor, $pickup, 'expired_pickup'));
        $this->assertFalse($policy->updateStatus($pickup->recipient, $pickup, 'expired_pickup'));
    }

    public function test_user_can_view_own_history(): void
    {
        $user = User::factory()->create();
        $policy = new PickupPolicy();

        $this->assertTrue($policy->viewHistory($user, null));
        $this->assertTrue($policy->viewHistory($user, $user->id));
    }

    public function test_user_cannot_view_another_users_history(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $policy = new PickupPolicy();

        $this->assertFalse($policy->viewHistory($user1, $user2->id));
    }

    public function test_admin_can_view_all_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $policy = new PickupPolicy();

        $this->assertTrue($policy->viewHistory($admin, null));
        $this->assertTrue($policy->viewHistory($admin, 999));
    }

    private function createPickup(): Pickup
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
}
