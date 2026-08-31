<?php

namespace App\Admin\Services;

use App\Enums\DonationStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\MatchStatus;
use App\Enums\PickupStatus;
use App\Enums\UserRole;
use App\Models\Donation;
use App\Models\DonationMatch;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\PlatformStat;
use App\Models\User;
use Illuminate\Support\Carbon;

class PlatformStatsAggregator
{
    /**
     * @return array<string, mixed>
     */
    public function aggregate(?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $periodStart ??= now()->startOfHour()->subHour();
        $periodEnd ??= now()->startOfHour();

        $metrics = [
            'users' => [
                'total' => User::query()->count(),
                'donors' => User::query()->where('role', UserRole::Donor)->count(),
                'recipients' => User::query()->where('role', UserRole::Recipient)->count(),
                'admins' => User::query()->where('role', UserRole::Admin)->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
            'donations' => [
                'total' => Donation::query()->count(),
                'available' => Donation::query()->where('status', DonationStatus::Available)->count(),
                'matched' => Donation::query()->where('status', DonationStatus::Matched)->count(),
                'completed' => Donation::query()->where('status', DonationStatus::Completed)->count(),
                'expired' => Donation::query()->where('status', DonationStatus::Expired)->count(),
            ],
            'requests' => [
                'total' => FoodRequest::query()->count(),
                'pending' => FoodRequest::query()->where('status', FoodRequestStatus::Pending)->count(),
                'approved' => FoodRequest::query()->where('status', FoodRequestStatus::Approved)->count(),
                'rejected' => FoodRequest::query()->where('status', FoodRequestStatus::Rejected)->count(),
            ],
            'matches' => [
                'total' => DonationMatch::query()->count(),
                'active' => DonationMatch::query()->where('status', MatchStatus::Active)->count(),
                'completed' => DonationMatch::query()->where('status', MatchStatus::Completed)->count(),
                'cancelled' => DonationMatch::query()->where('status', MatchStatus::Cancelled)->count(),
            ],
            'pickups' => [
                'total' => Pickup::query()->count(),
                'scheduled' => Pickup::query()->where('status', PickupStatus::Scheduled)->count(),
                'completed' => Pickup::query()->where('status', PickupStatus::Completed)->count(),
                'cancelled' => Pickup::query()->where('status', PickupStatus::Cancelled)->count(),
                'no_show' => Pickup::query()->where('status', PickupStatus::NoShow)->count(),
            ],
            'impact' => [
                'meals_redistributed' => (int) Donation::query()->where('status', DonationStatus::Completed)->sum('quantity'),
                'food_kg_saved' => (float) Donation::query()->where('status', DonationStatus::Completed)->sum('quantity'),
            ],
        ];

        PlatformStat::query()->create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'metrics' => $metrics,
            'created_at' => now(),
        ]);

        return $metrics;
    }
}
