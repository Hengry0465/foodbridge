<?php
namespace App\Http\Controllers\Api\V1;
use App\Admin\Services\AuditLogger;
use App\Enums\AuditActionType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function deactivate(
        User $user,
        AuditLogger $auditLogger,
    ): JsonResponse {
        if (! $user->is_active) {
            return response()->json([
                'message' => 'User is already deactivated.',
                'data' => ['id' => $user->id, 'is_active' => false],
            ]);
        }

        if ($user->id === request()->user()->id) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Other admin accounts cannot be deactivated.',
            ], 422);
        }

        $before = ['is_active' => true];
        $user->update(['is_active' => false]);

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
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }
}