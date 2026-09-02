<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Seeder for pickup_statuses lookup table
 */
class PickupStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'scheduled',
                'name' => 'Scheduled',
                'description' => 'Pickup has been scheduled by the recipient',
            ],
            [
                'code' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'Pickup has been confirmed by the donor',
            ],
            [
                'code' => 'completed',
                'name' => 'Completed',
                'description' => 'Pickup has been successfully completed',
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Pickup was cancelled by donor, recipient, or admin',
            ],
            [
                'code' => 'expired_pickup',
                'name' => 'Expired Pickup',
                'description' => 'Pickup expired due to lack of confirmation within time limit',
            ],
        ];

        DB::table('pickup_statuses')->insert($statuses);
    }
}
