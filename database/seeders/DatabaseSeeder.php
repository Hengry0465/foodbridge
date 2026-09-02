<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Only the PickupStatusSeeder is required for this module. Modules
        // 1-3 own their own seed data (users, donations, requests, matches).
        $this->call([
            PickupStatusSeeder::class,
        ]);
    }
}
