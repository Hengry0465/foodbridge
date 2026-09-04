<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\States\PickupState;
use App\Services\States\PickupStateFactory;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Pickup model with State pattern integration
 */
class Pickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'pickup_status_id',
        'donor_id',
        'recipient_id',
        'donation_id',
        'pickup_address',
        'scheduled_at',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'expired_at',
        'cancellation_reason',
        'donation_release_status',
        'donation_released_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'donation_released_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(PickupStatus::class, 'pickup_status_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchRecord::class, 'match_id');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function donation()
    {
        return $this->belongsTo(Donation::class, 'donation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the current state object for this pickup
     */
    public function getState(): PickupState
    {
        return PickupStateFactory::create($this);
    }

    /**
     * Check if this pickup is in a specific status
     */
    public function isStatus(string $code): bool
    {
        return $this->status && $this->status->code === $code;
    }

    /**
     * Check if the pickup is active (scheduled or confirmed)
     */
    public function isActive(): bool
    {
        return $this->isStatus('scheduled') || $this->isStatus('confirmed');
    }

    /**
     * Check if the pickup blocks a time slot
     */
    public function blocksTimeSlot(): bool
    {
        return $this->isActive();
    }

    /**
     * Normalize pickup address for comparison
     */
    public function getNormalizedAddress(): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $this->pickup_address)));
    }

    /**
     * Get time slot range for conflict detection
     */
    public function getTimeSlotRange(int $durationMinutes = 60): array
    {
        $start = $this->scheduled_at;
        $end = $this->scheduled_at->copy()->addMinutes($durationMinutes);

        return [$start, $end];
    }

    /**
     * Check if this pickup conflicts with another
     */
    public function conflictsWith(Pickup $other, int $durationMinutes = 60): bool
    {
        if (!$this->blocksTimeSlot() || !$other->blocksTimeSlot()) {
            return false;
        }

        if ($this->getNormalizedAddress() !== $other->getNormalizedAddress()) {
            return false;
        }

        if ($this->id === $other->id) {
            return false;
        }

        [$thisStart, $thisEnd] = $this->getTimeSlotRange($durationMinutes);
        [$otherStart, $otherEnd] = $other->getTimeSlotRange($durationMinutes);

        return $thisStart < $otherEnd && $otherStart < $thisEnd;
    }

    /**
     * Check if pickup is eligible for expiry
     */
    public function isEligibleForExpiry(): bool
    {
        if (!$this->isStatus('scheduled')) {
            return false;
        }

        $expiryThreshold = now()->subHours(2);
        return $this->scheduled_at->lt($expiryThreshold);
    }
}
