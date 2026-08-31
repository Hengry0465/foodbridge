<?php

namespace App\Admin\DTOs;

use Illuminate\Support\Carbon;

readonly class AdminDashboardFilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
        public ?string $status = null,
        public ?string $category = null,
        public ?string $region = null,
        public ?string $role = null,
        public ?bool $isActive = null,
        public ?string $actionType = null,
    ) {}

    public function hasFilters(): bool
    {
        return $this->search !== null
            || $this->from !== null
            || $this->to !== null
            || $this->status !== null
            || $this->category !== null
            || $this->region !== null
            || $this->role !== null
            || $this->isActive !== null
            || $this->actionType !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParams(string $tab): array
    {
        return array_filter([
            'tab' => $tab,
            'search' => $this->search,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'status' => $this->status,
            'category' => $this->category,
            'region' => $this->region,
            'role' => $this->role,
            'is_active' => $this->isActive === null ? null : ($this->isActive ? '1' : '0'),
            'action_type' => $this->actionType,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
