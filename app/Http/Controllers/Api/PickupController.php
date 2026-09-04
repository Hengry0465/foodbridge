<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePickupRequest;
use App\Http\Requests\UpdatePickupStatusRequest;
use App\Http\Requests\GetPickupHistoryRequest;
use App\Http\Resources\PickupResource;
use App\Http\Resources\PickupCollection;
use App\Models\Pickup;
use App\Services\PickupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupController - RESTful API endpoints for pickup operations
 * Controllers are thin; business logic is in the service layer
 */
class PickupController extends Controller
{
    private PickupService $pickupService;

    public function __construct(PickupService $pickupService)
    {
        $this->pickupService = $pickupService;
    }

    /**
     * POST /api/v1/pickups
     * Create a scheduled pickup for a successful match
     */
    public function store(StorePickupRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $pickup = $this->pickupService->schedulePickup($request->validated(), $user);

            return response()->json([
                'message' => 'Pickup scheduled successfully',
                'data' => new PickupResource($pickup),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Match not found'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            Log::error('Failed to schedule pickup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    /**
     * PATCH /api/v1/pickups/{pickup}/status
     * Update pickup status
     */
    public function updateStatus(UpdatePickupStatusRequest $request, Pickup $pickup): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $targetStatus = $request->input('status');
            $reason = $request->input('reason');

            // Authorization check
            if (!$user->can('updateStatus', [$pickup, $targetStatus])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $pickup = $this->pickupService->transitionStatus($pickup, $targetStatus, $reason);

            return response()->json([
                'message' => 'Pickup status updated successfully',
                'data' => new PickupResource($pickup),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            Log::error('Failed to update pickup status', [
                'pickup_id' => $pickup->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/v1/pickups/history
     * Get pickup history for authenticated user
     */
    public function history(GetPickupHistoryRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Prevent IDOR - reject if user tries to view another user's history
            $requestedUserId = $request->input('user_id');
            if (!$user->can('viewHistory', $requestedUserId)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $filters = $request->only(['status', 'date_from', 'date_to', 'page', 'per_page']);
            $pickups = $this->pickupService->getPickupHistory($user, $filters);

            return response()->json(new PickupCollection($pickups), 200);
        } catch (\Exception $e) {
            Log::error('Failed to get pickup history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    /**
     * GET /api/v1/pickups/{pickup}
     * View a specific pickup
     */
    public function show(Pickup $pickup): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if (!$user->can('view', $pickup)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'data' => new PickupResource($pickup),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to view pickup', [
                'pickup_id' => $pickup->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
