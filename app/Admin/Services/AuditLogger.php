<?php

namespace App\Admin\Services;

use App\Enums\AuditActionType;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $beforeValue
     * @param  array<string, mixed>|null  $afterValue
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        User $actor,
        AuditActionType $actionType,
        ?string $targetTable = null,
        ?int $targetId = null,
        ?array $beforeValue = null,
        ?array $afterValue = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor->id,
            'action_type' => $actionType,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'before_value' => $beforeValue !== null ? json_encode($beforeValue) : null,
            'after_value' => $afterValue !== null ? json_encode($afterValue) : null,
            'metadata' => $metadata !== null ? json_encode($metadata) : null,
            'created_at' => now(),
        ]);
    }
}
