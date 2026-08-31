<?php

namespace App\Services;

use App\Models\Pickup;
use App\Models\PickupStatus;
use App\Models\FoodMatch;
use App\Models\User;
use App\Services\States\PickupStateFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupService - handles pickup scheduling and state transitions
 */
class PickupService
{
    private int $pickupSlotDuration = 60; // minutes

    public function __construct()
    {
        $this->pickupSlotDuration = config('pickup.slot_duration', 60);
    }

    /**
     * Schedule a new pickup for a successful match
     */
    public function schedulePickup(array $data, User $user): Pickup
    {
        return DB::transaction(function () use ($data, $user) {
            $match = FoodMatch::with(['donor', 'recipient', 'donation'])
                ->findOrFail($data['match_id']);

            // Validate match is successful
            if (!$match->isSuccessful()) {
                throw new \InvalidArgumentException('Match is not successful');
            }

            // Validate user is the recipient
            if ($match->recipient_id !== $user->id) {
                throw new \InvalidArgumentException('Only the matched recipient can schedule a pickup');
            }

            // Check for existing active pickup
            $existingPickup = Pickup::where('match_id', $match->id)
                ->whereHas('status', function ($query) {
                    $query->whereIn('code', ['scheduled', 'confirmed']);
                })
                ->first();

            if ($existingPickup) {
                throw new \InvalidArgumentException('An active pickup already exists for this match');
            }

            // Validate scheduled time is in the future
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            if ($scheduledAt->lte(now())) {
                throw new \InvalidArgumentException('Scheduled time must be in the future');
            }

            // Get donor pickup address
            $pickupAddress = $this->getDonorPickupAddress($match);

            // Check for time-slot conflicts
            $this->checkTimeSlotConflict($pickupAddress, $scheduledAt);

            // Create the pickup
            $scheduledStatus = PickupStatus::where('code', 'scheduled')->firstOrFail();

            $pickup = Pickup::create([
                'match_id' => $match->id,
                'pickup_status_id' => $scheduledStatus->id,
                'donor_id' => $match->donor_id,
                'recipient_id' => $match->recipient_id,
                'donation_id' => $match->donation_id,
                'pickup_address' => $pickupAddress,
                'scheduled_at' => $scheduledAt,
                'created_by' => $user->id,
            ]);

            Log::info('Pickup scheduled', [
                'pickup_id' => $pickup->id,
                'match_id' => $match->id,
                'scheduled_at' => $scheduledAt,
            ]);

            return $pickup->fresh();
        });
    }

    /**
     * Transition pickup status using State pattern
     */
    public function transitionStatus(Pickup $pickup, string $targetStatus, ?string $reason = null): Pickup
    {
        return DB::transaction(function () use ($pickup, $targetStatus, $reason) {
            $state = $pickup->getState();

            return match ($targetStatus) {
                'confirmed' => $state->confirm($pickup, $reason),
                'completed' => $state->complete($pickup, $reason),
                'cancelled' => $state->cancel($pickup, $reason),
                'expired_pickup' => $state->expire($pickup),
                default => throw new \InvalidArgumentException("Invalid status transition: {$targetStatus}"),
            };
        });
    }

    /**
     * Check for time-slot conflicts at the same address
     */
    private function checkTimeSlotConflict(string $address, Carbon $scheduledAt): void
{
    $normalizedAddress = strtolower(trim(preg_replace('/\s+/', ' ', $address)));

    $startTime = $scheduledAt;
    $endTime = $scheduledAt->copy()->addMinutes($this->pickupSlotDuration);

    $conflictingPickups = Pickup::whereHas('status', function ($query) {
        $query->whereIn('code', ['scheduled', 'confirmed']);
    })
    ->whereRaw(
        'LOWER(TRIM(REPLACE(pickup_address, \'  \', \' \'))) = ?',
        [$normalizedAddress]
    )
    ->where(function ($query) use ($startTime, $endTime) {
        $query->where(function ($q) use ($startTime, $endTime) {
            $q->where('scheduled_at', '<', $endTime)
              ->whereRaw(
                  "datetime(scheduled_at, '+' || {$this->pickupSlotDuration} || ' minutes') > ?",
                  [$startTime]
              );
        });
    })
    ->lockForUpdate()
    ->exists();

    if ($conflictingPickups) {
        throw new \InvalidArgumentException(
            'Time slot conflict: another pickup is already scheduled at this address'
        );
    }
}    /**
     * Get donor pickup address from match
     * In a real implementation, this would come from the donor's profile or donation details
     */
    private function getDonorPickupAddress(FoodMatch $match): string
{
    return "123 Test Street";
}

    /**
     * Get pickup history for a user
     */
    public function getPickupHistory(User $user, array $filters = [])
    {
        $query = Pickup::with(['status', 'match', 'donor', 'recipient', 'donation']);

        // Apply role-based filtering
        if ($user->is_admin) {
            // Admins see all pickups
        } else {
            // Regular users see only their own pickups as donor or recipient
            $query->where(function ($q) use ($user) {
                $q->where('donor_id', $user->id)
                  ->orWhere('recipient_id', $user->id);
            });
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->whereHas('status', function ($q) use ($filters) {
                $q->where('code', $filters['status']);
            });
        }

        // Apply date range filters
        if (isset($filters['date_from'])) {
            $query->where('scheduled_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('scheduled_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // Sort newest first
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Expire eligible pickups (for scheduled command)
     */
    public function expireEligiblePickups(int $chunkSize = 100): int
    {
        $expiredCount = 0;

        Pickup::whereHas('status', function ($query) {
            $query->where('code', 'scheduled');
        })
        ->where('scheduled_at', '<', now()->subHours(2))
        ->chunkById($chunkSize, function ($pickups) use (&$expiredCount) {
            foreach ($pickups as $pickup) {
                try {
                    $this->transitionStatus($pickup, 'expired_pickup');
                    $expiredCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to expire pickup', [
                        'pickup_id' => $pickup->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $expiredCount;
    }
}
