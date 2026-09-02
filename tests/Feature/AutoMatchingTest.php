<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\FoodRequest;
use App\Models\User;
use App\Services\AutoMatchingService;
use App\Services\ModuleRequestSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutoMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_partially_matches_nearest_expiry_and_notifies_both_users(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $donor = User::factory()->create(['role' => 'donor']);
        $later = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Later meals', 'category' => 'Cooked Meals', 'quantity_available' => 5, 'expires_at' => now()->addHours(8), 'pickup_address' => 'B']);
        $sooner = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Sooner meals', 'category' => 'Cooked Meals', 'quantity_available' => 3, 'expires_at' => now()->addHours(2), 'pickup_address' => 'A']);
        Donation::create(['donor_id' => $donor->id, 'food_name' => 'Expired', 'category' => 'Cooked Meals', 'quantity_available' => 99, 'expires_at' => now()->subMinute(), 'pickup_address' => 'C']);
        $request = FoodRequest::create(['recipient_id' => $recipient->id, 'category' => 'Cooked Meals', 'quantity_requested' => 10, 'preferred_pickup_at' => now()->addHour()]);

        app(AutoMatchingService::class)->match($request);

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'partial', 'quantity_matched' => 8]);
        $this->assertDatabaseHas('matches', ['request_id' => $request->id, 'donation_id' => $sooner->id, 'quantity_allocated' => 3]);
        $this->assertDatabaseHas('matches', ['request_id' => $request->id, 'donation_id' => $later->id, 'quantity_allocated' => 5]);
        $this->assertDatabaseCount('match_notifications', 2);
    }

    public function test_api_is_idempotent_and_uses_standard_response_envelope(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $payload = ['recipient_id' => $recipient->id, 'category' => 'Bakery', 'quantity' => 4, 'preferred_pickup_at' => now()->addHour()->toIso8601String()];
        $requestId = 'client-001';

        $this->signedJson('POST', '/api/v1/requests', $payload, $requestId)
            ->assertCreated()
            ->assertJsonStructure(['requestID', 'timestamp', 'status', 'data' => ['id', 'status']]);
        $this->signedJson('POST', '/api/v1/requests', $payload, $requestId)
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_only_pending_request_can_be_withdrawn(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $pending = FoodRequest::create(['recipient_id' => $recipient->id, 'category' => 'Bakery', 'quantity_requested' => 2, 'preferred_pickup_at' => now()->addHour()]);
        $this->signedJson('DELETE', "/api/v1/requests/{$pending->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'withdrawn');

        $matched = FoodRequest::create(['recipient_id' => $recipient->id, 'category' => 'Bakery', 'quantity_requested' => 2, 'quantity_matched' => 2, 'status' => 'matched', 'preferred_pickup_at' => now()->addHour()]);
        $this->signedJson('DELETE', "/api/v1/requests/{$matched->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['requestID', 'timestamp', 'status', 'message']);
    }

    public function test_selected_donation_is_prioritised_before_expiry_order(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $donor = User::factory()->create(['role' => 'donor']);
        $expiringFirst = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Expiring first', 'category' => 'Bakery', 'quantity_available' => 10, 'expires_at' => now()->addHour(), 'pickup_address' => 'A']);
        $selected = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Recipient selected', 'category' => 'Bakery', 'quantity_available' => 10, 'expires_at' => now()->addHours(5), 'pickup_address' => 'B']);
        $request = FoodRequest::create(['recipient_id' => $recipient->id, 'preferred_donation_id' => $selected->id, 'category' => 'Bakery', 'quantity_requested' => 4, 'preferred_pickup_at' => now()->addHour()]);

        app(AutoMatchingService::class)->match($request);

        $this->assertDatabaseHas('matches', ['request_id' => $request->id, 'donation_id' => $selected->id, 'quantity_allocated' => 4]);
        $this->assertDatabaseMissing('matches', ['request_id' => $request->id, 'donation_id' => $expiringFirst->id]);
    }

    public function test_selected_donation_must_be_available_and_match_the_requested_category(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $donor = User::factory()->create(['role' => 'donor']);
        $donation = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Vegetables', 'category' => 'Fresh Produce', 'quantity_available' => 5, 'expires_at' => now()->addHours(5), 'pickup_address' => 'A']);

        $this->actingAs($recipient)->post('/recipient/requests', [
            'donation_id' => $donation->id,
            'category' => 'Bakery',
            'quantity' => 2,
            'preferred_pickup_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('donation_id');

        $this->assertDatabaseCount('requests', 0);
    }

    public function test_donation_card_submission_creates_match_and_notification(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $donor = User::factory()->create(['role' => 'donor']);
        $donation = Donation::create(['donor_id' => $donor->id, 'food_name' => 'Rice meals', 'category' => 'Cooked Meals', 'quantity_available' => 8, 'expires_at' => now()->addHours(5), 'pickup_address' => 'A']);

        $this->actingAs($recipient)->post('/recipient/requests', [
            'donation_id' => $donation->id,
            'category' => $donation->category,
            'quantity' => 3,
            'preferred_pickup_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect('/recipient/dashboard')->assertSessionHas('success');

        $this->assertDatabaseHas('requests', ['recipient_id' => $recipient->id, 'preferred_donation_id' => $donation->id, 'status' => 'matched', 'quantity_matched' => 3]);
        $this->assertDatabaseHas('match_notifications', ['user_id' => $recipient->id, 'type' => 'matched']);
    }

    private function signedJson(string $method, string $uri, array $payload = [], ?string $requestId = null)
    {
        $requestId ??= (string) Str::uuid();
        $timestamp = now()->toIso8601String();
        $moduleId = 'module-2-test';
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = app(ModuleRequestSigner::class)->sign(
            $moduleId,
            $requestId,
            $timestamp,
            $method,
            $uri,
            $body,
            (string) config('integrations.module_api_key'),
        );

        return $this->withHeaders([
            'X-Module-ID' => $moduleId,
            'X-Request-ID' => $requestId,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->json($method, $uri, $payload);
    }
}
