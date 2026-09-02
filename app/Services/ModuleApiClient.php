<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use UnexpectedValueException;

class ModuleApiClient
{
    public function __construct(private ModuleRequestSigner $signer) {}

    private function client(): PendingRequest
    {
        // Two attempts total = initial call plus the single retry required by the IFA.
        return Http::acceptJson()->timeout(3)->retry(2, 100);
    }

    public function verifyRecipient(int $userId): bool
    {
        if (! config('integrations.identity_url')) {
            return User::query()->whereKey($userId)->where('role', 'recipient')->exists();
        }

        $response = $this->signedGet(config('integrations.identity_url')."/api/v1/users/$userId");

        if (! $response->successful()) {
            return false;
        }

        $payload = $this->validatedEnvelope($response);

        return $payload['status'] === 'success'
            && (int) data_get($payload, 'data.id') === $userId
            && data_get($payload, 'data.role') === 'recipient';
    }

    /** @return array<int, array<string, mixed>>|null */
    public function availableDonations(string $category): ?array
    {
        if (! config('integrations.donation_url')) {
            return null;
        }

        $response = $this->signedGet(config('integrations.donation_url').'/api/v1/donations', [
            'category' => $category,
            'status' => 'available',
        ]);

        if (! $response->successful()) {
            return [];
        }

        $payload = $this->validatedEnvelope($response);

        return $payload['status'] === 'success' ? data_get($payload, 'data', []) : [];
    }

    private function signedGet(string $url, array $query = []): Response
    {
        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $requestTarget = (string) parse_url($url, PHP_URL_PATH).($queryString === '' ? '' : '?'.$queryString);
        $requestId = (string) Str::uuid();
        $timestamp = now()->toIso8601String();
        $moduleId = (string) config('integrations.module_id', 'module-3');
        $secret = (string) config('integrations.module_api_key');

        $headers = [
            'X-Module-ID' => $moduleId,
            'X-Request-ID' => $requestId,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->signer->sign($moduleId, $requestId, $timestamp, 'GET', $requestTarget, '', $secret),
        ];

        return $this->client()->withHeaders($headers)->get($url, $query);
    }

    /** @return array<string, mixed> */
    private function validatedEnvelope(Response $response): array
    {
        $payload = $response->json();
        if (! is_array($payload) || ! isset($payload['status'], $payload['timestamp'])) {
            throw new UnexpectedValueException('The consumed service returned an invalid IFA envelope.');
        }

        try {
            CarbonImmutable::parse((string) $payload['timestamp']);
        } catch (\Throwable $exception) {
            throw new UnexpectedValueException('The consumed service returned an invalid response timestamp.', previous: $exception);
        }

        return $payload;
    }
}
