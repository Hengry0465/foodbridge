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
 * Feature tests for pickup scheduling
 */
class PickupSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PickupStatusSeeder::class);
    }

    public function test_recipient_can_schedule_pickup_for_own_successful_match(): void
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

        $service = new PickupService();
        $pickup = $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->addHours(2)->toIso8601String(),
        ], $recipient);

        $this->assertDatabaseHas('pickups', [
            'id' => $pickup->id,
            'match_id' => $match->id,
            'recipient_id' => $recipient->id,
            'donor_id' => $donor->id,
        ]);
        $this->assertEquals('scheduled', $pickup->status->code);
    }

    public function test_recipient_cannot_schedule_for_another_recipients_match(): void
    {
        $recipient1 = User::factory()->create();
        $recipient2 = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient1->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->addHours(2)->toIso8601String(),
        ], $recipient2);
    }

    public function test_donor_cannot_schedule_recipients_pickup(): void
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

        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->addHours(2)->toIso8601String(),
        ], $donor);
    }

    public function test_scheduling_fails_for_unsuccessful_match(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'pending',
        ]);

        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->addHours(2)->toIso8601String(),
        ], $recipient);
    }

    public function test_scheduling_fails_when_active_pickup_exists(): void
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

        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->addHours(4)->toIso8601String(),
        ], $recipient);
    }

    public function test_past_date_is_rejected(): void
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

        $service = new PickupService();

        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match->id,
            'scheduled_at' => now()->subHour()->toIso8601String(),
        ], $recipient);
    }

    public function test_same_address_overlapping_slot_is_rejected(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match1 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $match2 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        $address = "123 Test Street";

        Pickup::factory()->create([
            'match_id' => $match1->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $match1->donation_id,
            'pickup_address' => $address,
            'scheduled_at' => now()->addHours(2),
        ]);

        $service = new PickupService();

        // This should fail due to overlapping time slot
        $this->expectException(\InvalidArgumentException::class);
        $service->schedulePickup([
            'match_id' => $match2->id,
            'scheduled_at' => now()->addHours(2)->addMinutes(30)->toIso8601String(),
        ], $recipient);
    }

    public function test_non_overlapping_slot_succeeds(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match1 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $match2 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
            'status' => 'successful',
        ]);

        $scheduledStatus = PickupStatus::where('code', 'scheduled')->first();
        $address = "123 Test Street";

        Pickup::factory()->create([
            'match_id' => $match1->id,
            'pickup_status_id' => $scheduledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $match1->donation_id,
            'pickup_address' => $address,
            'scheduled_at' => now()->addHours(2),
        ]);

        $service = new PickupService();

        // This should succeed - non-overlapping time slot (2 hours later)
        $pickup = $service->schedulePickup([
            'match_id' => $match2->id,
            'scheduled_at' => now()->addHours(4)->toIso8601String(),
        ], $recipient);

        $this->assertDatabaseHas('pickups', ['id' => $pickup->id]);
    }

    public function test_cancelled_pickup_no_longer_blocks_slot(): void
    {
        $recipient = User::factory()->create();
        $donor = User::factory()->create();
        $donation = Donation::factory()->create(['donor_id' => $donor->id]);
        $match1 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $donation->id,
            'status' => 'successful',
        ]);

        $match2 = FoodMatch::factory()->create([
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => Donation::factory()->create(['donor_id' => $donor->id])->id,
            'status' => 'successful',
        ]);

        $cancelledStatus = PickupStatus::where('code', 'cancelled')->first();
        $address = "123 Test Street";

        Pickup::factory()->create([
            'match_id' => $match1->id,
            'pickup_status_id' => $cancelledStatus->id,
            'donor_id' => $donor->id,
            'recipient_id' => $recipient->id,
            'donation_id' => $match1->donation_id,
            'pickup_address' => $address,
            'scheduled_at' => now()->addHours(2),
            'cancelled_at' => now(),
        ]);

        $service = new PickupService();

        // This should succeed - cancelled pickups don't block slots
        $pickup = $service->schedulePickup([
            'match_id' => $match2->id,
            'scheduled_at' => now()->addHours(2)->addMinutes(30)->toIso8601String(),
        ], $recipient);

        $this->assertDatabaseHas('pickups', ['id' => $pickup->id]);
    }
}
