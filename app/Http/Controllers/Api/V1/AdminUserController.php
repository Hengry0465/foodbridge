<?php

namespace App\Http\Controllers\Api\V1;

use App\Admin\Services\AuditLogger;
use App\Admin\Services\ModuleOneUserClient;
use App\Enums\AuditActionType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function deactivate(
        User $user,
        ModuleOneUserClient $userClient,
        AuditLogger $auditLogger,
    ): JsonResponse {
        if (! $user->is_active) {
            return response()->json([
                'message' => 'User is already deactivated.',
                'data' => ['id' => $user->id, 'is_active' => false],
            ]);
        }

        $before = ['is_active' => true];
        $updatedUser = $userClient->deactivate(request()->user(), $user);

        $auditLogger->log(
            actor: request()->user(),
            actionType: AuditActionType::UserDeactivated,
            targetTable: 'users',
            targetId: $user->id,
            beforeValue: $before,
            afterValue: ['is_active' => false],
        );

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => [
                'id' => $updatedUser->id,
                'is_active' => $updatedUser->is_active,
            ],
        ]);
    }
}
