<?php

namespace Database\Seeders;

use App\Enums\FoodRegion;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@foodbridge.test'],
            [
                'name' => 'Platform Admin',
                'username' => 'admin',
                'region' => FoodRegion::KualaLumpur,
                'password' => 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );
    }
}
