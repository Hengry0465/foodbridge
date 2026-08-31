<?php

namespace App\Admin\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ModuleOneUserClient
{
    public function deactivate(User $admin, User $target): User
    {
        $baseUrl = rtrim((string) config('services.modules.user', config('app.url')), '/');

        if ($this->shouldUseInternalCall($baseUrl)) {
            return $this->deactivateDirectly($target);
        }

        $response = Http::withToken($admin->createToken('admin-action')->plainTextToken)
            ->acceptJson()
            ->patch("{$baseUrl}/api/v1/admin/users/{$target->id}/deactivate");

        if ($response->failed()) {
            throw new RuntimeException($response->json('message', 'Failed to deactivate user.'));
        }

        return $target->fresh() ?? $target;
    }

    private function shouldUseInternalCall(string $baseUrl): bool
    {
        return $baseUrl === rtrim((string) config('app.url'), '/');
    }

    private function deactivateDirectly(User $target): User
    {
        if (! $target->is_active) {
            return $target;
        }

        $target->update(['is_active' => false]);

        return $target->fresh();
    }
}
