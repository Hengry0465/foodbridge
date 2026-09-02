<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleRequestSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_and_stale_api_requests_are_rejected_with_ifa_envelopes(): void
    {
        $this->postJson('/api/v1/requests', [])
            ->assertUnauthorized()
            ->assertJsonStructure(['requestID', 'timestamp', 'status', 'message']);

        $timestamp = now()->subMinutes(10)->toIso8601String();
        $requestId = 'stale-request-001';
        $signature = app(ModuleRequestSigner::class)->sign(
            'module-2-test',
            $requestId,
            $timestamp,
            'GET',
            '/api/v1/requests/missing/match',
            '',
            (string) config('integrations.module_api_key'),
        );

        $this->withHeaders([
            'X-Module-ID' => 'module-2-test',
            'X-Request-ID' => $requestId,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->getJson('/api/v1/requests/missing/match')
            ->assertStatus(408)
            ->assertJsonPath('status', 'error');
    }

    public function test_reused_request_id_with_a_different_payload_is_rejected(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $basePayload = [
            'recipient_id' => $recipient->id,
            'category' => 'Bakery',
            'quantity' => 4,
            'preferred_pickup_at' => now()->addHour()->toIso8601String(),
        ];

        $this->signedPost($basePayload, 'replay-protected-001')->assertCreated();
        $this->signedPost([...$basePayload, 'quantity' => 9], 'replay-protected-001')
            ->assertStatus(409)
            ->assertJsonPath('status', 'idempotency_conflict');
    }

    public function test_web_actions_use_authenticated_recipient_instead_of_submitted_identity(): void
    {
        $recipient = User::factory()->create(['role' => 'recipient']);
        $otherRecipient = User::factory()->create(['role' => 'recipient']);

        $this->actingAs($recipient)->post('/recipient/requests', [
            'recipient_id' => $otherRecipient->id,
            'category' => 'Bakery',
            'quantity' => 2,
            'preferred_pickup_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('requests', 0);
    }

    private function signedPost(array $payload, string $requestId)
    {
        $timestamp = now()->toIso8601String();
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = app(ModuleRequestSigner::class)->sign(
            'module-2-test',
            $requestId,
            $timestamp,
            'POST',
            '/api/v1/requests',
            $body,
            (string) config('integrations.module_api_key'),
        );

        return $this->withHeaders([
            'X-Module-ID' => 'module-2-test',
            'X-Request-ID' => $requestId,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->postJson('/api/v1/requests', $payload);
    }
}
