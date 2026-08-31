<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Services\AuditLogger;
use App\Admin\Services\ModuleOneUserClient;
use App\Enums\AuditActionType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
        ];

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ]);

        $auditLogger->log(
            actor: $request->user(),
            actionType: AuditActionType::UserUpdated,
            targetTable: 'users',
            targetId: $user->id,
            beforeValue: $before,
            afterValue: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ],
            metadata: ['source' => 'admin_user_management'],
        );

        return redirect()
            ->route('admin.dashboard', ['tab' => 'users'])
            ->with('status', "User {$user->email} has been updated.");
    }

    public function deactivate(
        User $user,
        ModuleOneUserClient $userClient,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        if (! $user->is_active) {
            return back()->with('status', 'User is already deactivated.');
        }

        if ($user->role === UserRole::Admin && $user->id !== request()->user()->id) {
            return back()->withErrors(['user' => 'Other admin accounts cannot be deactivated.']);
        }

        if ($user->id === request()->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $userClient->deactivate(request()->user(), $user);

        $auditLogger->log(
            actor: request()->user(),
            actionType: AuditActionType::UserDeactivated,
            targetTable: 'users',
            targetId: $user->id,
            beforeValue: ['is_active' => true],
            afterValue: ['is_active' => false],
            metadata: ['source' => 'admin_user_management'],
        );

        return back()->with('status', "User {$user->email} has been deactivated.");
    }

    public function activate(User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if ($user->is_active) {
            return back()->with('status', 'User is already active.');
        }

        $user->update(['is_active' => true]);

        $auditLogger->log(
            actor: request()->user(),
            actionType: AuditActionType::UserActivated,
            targetTable: 'users',
            targetId: $user->id,
            beforeValue: ['is_active' => false],
            afterValue: ['is_active' => true],
            metadata: ['source' => 'admin_user_management'],
        );

        return back()->with('status', "User {$user->email} has been reactivated.");
    }
}
