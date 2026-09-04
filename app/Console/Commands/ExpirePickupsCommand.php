<?php

namespace App\Console\Commands;

use App\Services\PickupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * ExpirePickupsCommand - Artisan command to expire eligible pickups
 * Runs every minute to check for pickups that remain unconfirmed
 * for more than 2 hours past their scheduled time
 */
class ExpirePickupsCommand extends Command
{
    protected $signature = 'pickups:expire';
    protected $description = 'Expire pickups that remain unconfirmed for more than 2 hours';

    private PickupService $pickupService;

    public function __construct(PickupService $pickupService)
    {
        parent::__construct();
        $this->pickupService = $pickupService;
    }

    public function handle(): int
    {
        try {
            $this->info('Starting pickup expiry check...');
            
            $expiredCount = $this->pickupService->expireEligiblePickups();
            
            $this->info("Expired {$expiredCount} pickup(s).");
            
            Log::info('Pickup expiry command completed', [
                'expired_count' => $expiredCount,
            ]);
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to expire pickups: ' . $e->getMessage());
            
            Log::error('Pickup expiry command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return self::FAILURE;
        }
    }
}
