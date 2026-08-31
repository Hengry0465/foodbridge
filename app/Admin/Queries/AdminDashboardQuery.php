<?php

namespace App\Admin\Queries;

use App\Admin\DTOs\AdminDashboardFilterDto;
use App\Enums\DonationStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\MatchStatus;
use App\Enums\PickupStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\DonationMatch;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardQuery
{
    public function __construct(private AdminDashboardFilterDto $filters) {}

    /**
     * @return Builder<User>
     */
    public function users(): Builder
    {
        $query = User::query()->latest();

        $this->applyDateRange($query);
        $this->applyRole($query);
        $this->applyActiveStatus($query);
        $this->applyRegion($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * @return Builder<Donation>
     */
    public function donations(): Builder
    {
        $query = Donation::query()->with('donor:id,name,email,username')->latest();

        $this->applyDateRange($query);
        $this->applyStatus($query);
        $this->applyCategory($query);
        $this->applyRegion($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->whereHas('donor', function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * @return Builder<FoodRequest>
     */
    public function requests(): Builder
    {
        $query = FoodRequest::query()->with('recipient:id,name,email,username')->latest();

        $this->applyDateRange($query);
        $this->applyStatus($query);
        $this->applyCategory($query);
        $this->applyRegion($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->whereHas('recipient', function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * @return Builder<DonationMatch>
     */
    public function matches(): Builder
    {
        $query = DonationMatch::query()
            ->with(['donation:id,category', 'foodRequest:id,category'])
            ->latest();

        $this->applyDateRange($query, 'matched_at');
        $this->applyStatus($query);
        $this->applyRegionViaDonation($query);

        return $query;
    }

    /**
     * @return Builder<Pickup>
     */
    public function pickups(): Builder
    {
        $query = Pickup::query()->with('donationMatch:id,donation_id,request_id')->latest();

        $this->applyDateRange($query, 'scheduled_at');
        $this->applyStatus($query);
        $this->applyRegionViaDonationMatch($query);

        return $query;
    }

    /**
     * @return Builder<AuditLog>
     */
    public function auditLogs(): Builder
    {
        $query = AuditLog::query()->with('actor:id,name')->latest('created_at');

        $this->applyDateRange($query);
        $this->applyActionType($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->whereHas('actor', function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function overviewMetrics(): array
    {
        $userQuery = User::query();
        $donationQuery = Donation::query();
        $requestQuery = FoodRequest::query();
        $matchQuery = DonationMatch::query();
        $pickupQuery = Pickup::query();

        if ($this->filters->from !== null || $this->filters->to !== null) {
            $this->applyDateRange($userQuery);
            $this->applyDateRange($donationQuery);
            $this->applyDateRange($requestQuery);
            $this->applyDateRange($matchQuery, 'matched_at');
            $this->applyDateRange($pickupQuery, 'scheduled_at');
        }

        if ($this->filters->role !== null) {
            $userQuery->where('role', $this->filters->role);
        }

        if ($this->filters->region !== null) {
            $userQuery->where('region', $this->filters->region);
            $donationQuery->where('region', $this->filters->region);
            $requestQuery->where('region', $this->filters->region);
            $matchQuery->whereHas('donation', fn (Builder $builder): Builder => $builder->where('region', $this->filters->region));
            $pickupQuery->whereHas('donationMatch.donation', fn (Builder $builder): Builder => $builder->where('region', $this->filters->region));
        }

        return [
            'users' => [
                'total' => (clone $userQuery)->count(),
                'donors' => (clone $userQuery)->where('role', UserRole::Donor)->count(),
                'recipients' => (clone $userQuery)->where('role', UserRole::Recipient)->count(),
                'admins' => (clone $userQuery)->where('role', UserRole::Admin)->count(),
                'active' => (clone $userQuery)->where('is_active', true)->count(),
                'inactive' => (clone $userQuery)->where('is_active', false)->count(),
            ],
            'donations' => [
                'total' => (clone $donationQuery)->count(),
                'available' => (clone $donationQuery)->where('status', DonationStatus::Available)->count(),
                'matched' => (clone $donationQuery)->where('status', DonationStatus::Matched)->count(),
                'completed' => (clone $donationQuery)->where('status', DonationStatus::Completed)->count(),
                'expired' => (clone $donationQuery)->where('status', DonationStatus::Expired)->count(),
            ],
            'requests' => [
                'total' => (clone $requestQuery)->count(),
                'pending' => (clone $requestQuery)->where('status', FoodRequestStatus::Pending)->count(),
                'approved' => (clone $requestQuery)->where('status', FoodRequestStatus::Approved)->count(),
                'rejected' => (clone $requestQuery)->where('status', FoodRequestStatus::Rejected)->count(),
            ],
            'matches' => [
                'total' => (clone $matchQuery)->count(),
                'active' => (clone $matchQuery)->where('status', MatchStatus::Active)->count(),
                'completed' => (clone $matchQuery)->where('status', MatchStatus::Completed)->count(),
                'cancelled' => (clone $matchQuery)->where('status', MatchStatus::Cancelled)->count(),
            ],
            'pickups' => [
                'total' => (clone $pickupQuery)->count(),
                'scheduled' => (clone $pickupQuery)->where('status', PickupStatus::Scheduled)->count(),
                'completed' => (clone $pickupQuery)->where('status', PickupStatus::Completed)->count(),
                'cancelled' => (clone $pickupQuery)->where('status', PickupStatus::Cancelled)->count(),
                'no_show' => (clone $pickupQuery)->where('status', PickupStatus::NoShow)->count(),
            ],
            'impact' => [
                'meals_redistributed' => (int) (clone $donationQuery)->where('status', DonationStatus::Completed)->sum('quantity'),
                'food_kg_saved' => (float) (clone $donationQuery)->where('status', DonationStatus::Completed)->sum('quantity'),
            ],
        ];
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applyDateRange(Builder $query, string $column = 'created_at'): void
    {
        if ($this->filters->from !== null) {
            $query->where($column, '>=', $this->filters->from);
        }

        if ($this->filters->to !== null) {
            $query->where($column, '<=', $this->filters->to);
        }
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applyStatus(Builder $query): void
    {
        if ($this->filters->status !== null) {
            $query->where('status', $this->filters->status);
        }
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applyCategory(Builder $query): void
    {
        if ($this->filters->category !== null) {
            $query->where('category', $this->filters->category);
        }
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applyRegion(Builder $query): void
    {
        if ($this->filters->region !== null) {
            $query->where('region', $this->filters->region);
        }
    }

    /**
     * @param  Builder<DonationMatch>  $query
     */
    private function applyRegionViaDonation(Builder $query): void
    {
        if ($this->filters->region !== null) {
            $query->whereHas('donation', fn (Builder $builder): Builder => $builder->where('region', $this->filters->region));
        }
    }

    /**
     * @param  Builder<Pickup>  $query
     */
    private function applyRegionViaDonationMatch(Builder $query): void
    {
        if ($this->filters->region !== null) {
            $query->whereHas('donationMatch.donation', fn (Builder $builder): Builder => $builder->where('region', $this->filters->region));
        }
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyRole(Builder $query): void
    {
        if ($this->filters->role !== null) {
            $query->where('role', $this->filters->role);
        }
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyActiveStatus(Builder $query): void
    {
        if ($this->filters->isActive !== null) {
            $query->where('is_active', $this->filters->isActive);
        }
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function applyActionType(Builder $query): void
    {
        if ($this->filters->actionType !== null) {
            $query->where('action_type', $this->filters->actionType);
        }
    }
}
