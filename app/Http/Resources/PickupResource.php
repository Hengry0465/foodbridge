<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * PickupResource - API resource for pickup model
 */
class PickupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'match_id' => $this->match_id,
            'status' => [
                'id' => $this->pickup_status_id,
                'code' => $this->status?->code,
                'name' => $this->status?->name,
            ],
            'donor' => [
                'id' => $this->donor_id,
                'name' => $this->donor?->name,
            ],
            'recipient' => [
                'id' => $this->recipient_id,
                'name' => $this->recipient?->name,
            ],
            'donation' => [
                'id' => $this->donation_id,
                'title' => $this->donation?->title,
            ],
            'pickup_address' => $this->pickup_address,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'donation_release_status' => $this->donation_release_status,
            'donation_released_at' => $this->donation_released_at?->toIso8601String(),
            'created_by' => [
                'id' => $this->created_by,
                'name' => $this->createdBy?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
