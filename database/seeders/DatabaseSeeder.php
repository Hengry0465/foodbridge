<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $recipient = User::updateOrCreate(
            ['email' => 'recipient@foodbridge.test'],
            ['name' => 'Hope Community Kitchen', 'role' => 'recipient', 'password' => Hash::make('password')],
        );
        $donor = User::updateOrCreate(
            ['email' => 'donor@foodbridge.test'],
            ['name' => 'Sunrise Food Market', 'role' => 'donor', 'password' => Hash::make('password')],
        );

        Donation::updateOrCreate(
            ['donor_id' => $donor->id, 'food_name' => 'Fresh vegetable boxes'],
            ['category' => 'Fresh Produce', 'quantity_available' => 25, 'expires_at' => now()->addDays(2), 'pickup_address' => '12 Jalan Melati, Kuala Lumpur', 'status' => 'available'],
        );
        Donation::updateOrCreate(
            ['donor_id' => $donor->id, 'food_name' => 'Packed rice meals'],
            ['category' => 'Cooked Meals', 'quantity_available' => 40, 'expires_at' => now()->addDay(), 'pickup_address' => '12 Jalan Melati, Kuala Lumpur', 'status' => 'available'],
        );
    }
}
