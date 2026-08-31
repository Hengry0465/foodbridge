<?php

namespace App\Admin\DTOs;

use App\Enums\ReportType;
use Illuminate\Support\Carbon;

readonly class ReportFilterDto
{
    public function __construct(
        public ReportType $type,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
        public ?string $status = null,
        public ?string $category = null,
        public ?string $region = null,
        public ?string $role = null,
        public ?bool $isActive = null,
        public ?int $actorId = null,
        public ?string $actionType = null,
        public int $perPage = 25,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'status' => $this->status,
            'category' => $this->category,
            'region' => $this->region,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'actor_id' => $this->actorId,
            'action_type' => $this->actionType,
            'per_page' => $this->perPage,
        ], fn (mixed $value): bool => $value !== null);
    }
}
