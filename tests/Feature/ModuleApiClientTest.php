<?php

namespace Tests\Feature;

use App\Services\ModuleApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModuleApiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumed_services_receive_tracking_and_signature_headers(): void
    {
        config([
            'integrations.identity_url' => 'https://identity.foodbridge.test',
            'integrations.donation_url' => 'https://donations.foodbridge.test',
        ]);

        Http::fake([
            'https://identity.foodbridge.test/*' => Http::response([
                'requestID' => 'identity-response-001',
                'timestamp' => now()->toIso8601String(),
                'status' => 'success',
                'data' => ['id' => 7, 'role' => 'recipient'],
            ]),
            'https://donations.foodbridge.test/*' => Http::response([
                'requestID' => 'donation-response-001',
                'timestamp' => now()->toIso8601String(),
                'status' => 'success',
                'data' => [['id' => 21, 'category' => 'Bakery']],
            ]),
        ]);

        $client = app(ModuleApiClient::class);
        $this->assertTrue($client->verifyRecipient(7));
        $this->assertSame(21, $client->availableDonations('Bakery')[0]['id']);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Module-ID', 'module-3-test')
            && $request->hasHeader('X-Request-ID')
            && $request->hasHeader('X-Timestamp')
            && $request->hasHeader('X-Signature'));
    }

    public function test_invalid_consumed_response_envelope_is_rejected(): void
    {
        config(['integrations.identity_url' => 'https://identity.foodbridge.test']);
        Http::fake(['*' => Http::response(['data' => ['id' => 7, 'role' => 'recipient']])]);

        $this->expectException(\UnexpectedValueException::class);
        app(ModuleApiClient::class)->verifyRecipient(7);
    }
}
