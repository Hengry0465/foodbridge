<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFoodRequest;
use App\Models\FoodRequest;
use App\Services\AutoMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodRequestController extends Controller
{
    public function store(StoreFoodRequest $request, AutoMatchingService $matcher): JsonResponse
    {
        $validated = $request->validated();
        $clientId = $validated['request_id'];
        $fingerprint = hash('sha256', json_encode([
            'recipient_id' => $validated['recipient_id'],
            'donation_id' => $validated['donation_id'] ?? null,
            'category' => $validated['category'],
            'quantity' => $validated['quantity'],
            'preferred_pickup_at' => $validated['preferred_pickup_at'],
        ], JSON_THROW_ON_ERROR));

        if ($existing = FoodRequest::where('client_request_id', $clientId)->first()) {
            if (! hash_equals((string) $existing->request_fingerprint, $fingerprint)) {
                return $this->error('The requestID has already been used for a different payload.', 409, 'idempotency_conflict');
            }

            return $this->respond($existing->load('matches.donation'), 200, 'duplicate');
        }
        $foodRequest = DB::transaction(function () use ($validated, $clientId, $fingerprint, $matcher): FoodRequest {
            $foodRequest = FoodRequest::create([
                'recipient_id' => $validated['recipient_id'],
                'preferred_donation_id' => $validated['donation_id'] ?? null,
                'category' => $validated['category'],
                'quantity_requested' => $validated['quantity'],
                'preferred_pickup_at' => $validated['preferred_pickup_at'],
                'client_request_id' => $clientId,
                'request_fingerprint' => $fingerprint,
            ]);

            return $matcher->match($foodRequest);
        }, 3);

        return $this->respond($foodRequest, 201);
    }

    public function showMatch(FoodRequest $foodRequest): JsonResponse
    {
        return $this->respond($foodRequest->load('matches.donation'));
    }

    public function destroy(Request $request, FoodRequest $foodRequest): JsonResponse
    {
        if ($foodRequest->status !== 'pending') {
            return $this->error('Only an unmatched pending request can be withdrawn.', 409);
        }
        $foodRequest->update(['status' => 'withdrawn']);

        return $this->respond($foodRequest);
    }

    private function respond(FoodRequest $foodRequest, int $code = 200, string $status = 'success'): JsonResponse
    {
        return response()->json([
            'requestID' => request()->attributes->get('requestID', (string) $foodRequest->id),
            'timestamp' => now()->toIso8601String(),
            'status' => $status,
            'data' => $foodRequest,
        ], $code);
    }

    private function error(string $message, int $code, string $status = 'error'): JsonResponse
    {
        return response()->json([
            'requestID' => request()->attributes->get('requestID', request()->header('X-Request-ID')),
            'timestamp' => now()->toIso8601String(),
            'status' => $status,
            'message' => $message,
        ], $code);
    }
}
