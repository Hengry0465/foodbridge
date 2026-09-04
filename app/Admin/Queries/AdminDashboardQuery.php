<?php

namespace App\Admin\Queries;

use App\Admin\DTOs\AdminDashboardFilterDto;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\MatchRecord;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardQuery
{
    public function __construct(private AdminDashboardFilterDto $filters) {}

    public function users(): Builder
    {
        $query = User::query()->latest();

        $this->applyDateRange($query, 'matched_at');
        $this->applyRole($query);
        $this->applyActiveStatus($query);

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

    public function donations(): Builder
    {
        $query = Donation::query()->with(['donor:id,name,email', 'category:id,name'])->latest();

        $this->applyDateRange($query);
        $this->applyStatus($query);
        $this->applyDonationCategory($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->whereHas('donor', function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function requests(): Builder
    {
        $query = FoodRequest::query()->with('recipient:id,name,email')->latest();

        $this->applyDateRange($query);
        $this->applyStatus($query);
        $this->applyCategory($query);

        if ($this->filters->search !== null) {
            $search = $this->filters->search;
            $query->whereHas('recipient', function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function matches(): Builder
    {
        $query = MatchRecord::query()
            ->with(['donation:id,category_id,food_name', 'foodRequest:id,category'])
            ->latest();

        $this->applyDateRange($query);
        $this->applyStatus($query);

        return $query;
    }

    public function pickups(): Builder
    {
        $query = Pickup::query()->with('match:id,donation_id,request_id')->latest();

        $this->applyDateRange($query, 'scheduled_at');
        $this->applyPickupStatus($query);

        return $query;
    }

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
        $matchQuery = MatchRecord::query();
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

        return [
            'users' => [
                'total' => (clone $userQuery)->count(),
                'donors' => (clone $userQuery)->where('role', 'donor')->count(),
                'recipients' => (clone $userQuery)->where('role', 'recipient')->count(),
                'admins' => (clone $userQuery)->where('role', 'admin')->count(),
                'active' => (clone $userQuery)->where('is_active', true)->count(),
                'inactive' => (clone $userQuery)->where('is_active', false)->count(),
            ],
            'donations' => [
                'total' => (clone $donationQuery)->count(),
                'available' => (clone $donationQuery)->where('status', 'available')->count(),
                'expiring_soon' => (clone $donationQuery)->where('status', 'expiring_soon')->count(),
                'reserved' => (clone $donationQuery)->where('status', 'reserved')->count(),
                'completed' => (clone $donationQuery)->where('status', 'completed')->count(),
                'expired' => (clone $donationQuery)->where('status', 'expired')->count(),
                'cancelled' => (clone $donationQuery)->where('status', 'cancelled')->count(),
            ],
            'requests' => [
                'total' => (clone $requestQuery)->count(),
                'pending' => (clone $requestQuery)->where('status', 'pending')->count(),
                'matched' => (clone $requestQuery)->where('status', 'matched')->count(),
                'partial' => (clone $requestQuery)->where('status', 'partial')->count(),
                'withdrawn' => (clone $requestQuery)->where('status', 'withdrawn')->count(),
            ],
            'matches' => [
                'total' => (clone $matchQuery)->count(),
                'confirmed' => (clone $matchQuery)->where('status', 'confirmed')->count(),
            ],
            'pickups' => [
                'total' => (clone $pickupQuery)->count(),
                'scheduled' => (clone $pickupQuery)->whereHas('status', fn (Builder $q) => $q->where('code', 'scheduled'))->count(),
                'confirmed' => (clone $pickupQuery)->whereHas('status', fn (Builder $q) => $q->where('code', 'confirmed'))->count(),
                'completed' => (clone $pickupQuery)->whereHas('status', fn (Builder $q) => $q->where('code', 'completed'))->count(),
                'cancelled' => (clone $pickupQuery)->whereHas('status', fn (Builder $q) => $q->where('code', 'cancelled'))->count(),
                'expired_pickup' => (clone $pickupQuery)->whereHas('status', fn (Builder $q) => $q->where('code', 'expired_pickup'))->count(),
            ],
            'impact' => [
                'meals_redistributed' => (int) (clone $donationQuery)->where('status', 'completed')->sum('quantity'),
                'food_kg_saved' => (float) (clone $donationQuery)->where('status', 'completed')->sum('quantity'),
            ],
        ];
    }

    private function applyDateRange(Builder $query, string $column = 'created_at'): void
    {
        if ($this->filters->from !== null) {
            $query->where($column, '>=', $this->filters->from);
        }

        if ($this->filters->to !== null) {
            $query->where($column, '<=', $this->filters->to);
        }
    }

    private function applyStatus(Builder $query): void
    {
        if ($this->filters->status !== null) {
            $query->where('status', $this->filters->status);
        }
    }

    private function applyPickupStatus(Builder $query): void
    {
        if ($this->filters->status !== null) {
            $status = $this->filters->status;
            $query->whereHas('status', fn (Builder $builder) => $builder->where('code', $status));
        }
    }

    private function applyCategory(Builder $query): void
    {
        if ($this->filters->category !== null) {
            $query->where('category', $this->filters->category);
        }
    }

    private function applyDonationCategory(Builder $query): void
    {
        if ($this->filters->category !== null) {
            $category = $this->filters->category;
            $query->whereHas('category', fn (Builder $builder) => $builder->where('name', $category));
        }
    }

    private function applyRole(Builder $query): void
    {
        if ($this->filters->role !== null) {
            $query->where('role', $this->filters->role);
        }
    }

    private function applyActiveStatus(Builder $query): void
    {
        if ($this->filters->isActive !== null) {
            $query->where('is_active', $this->filters->isActive);
        }
    }

    private function applyActionType(Builder $query): void
    {
        if ($this->filters->actionType !== null) {
            $query->where('action_type', $this->filters->actionType);
        }
    }
}