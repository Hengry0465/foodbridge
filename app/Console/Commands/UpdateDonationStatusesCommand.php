<?php
namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\DonationTypeExpiryRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateDonationStatusesCommand extends Command
{
    protected $signature = 'donations:update-status';
    protected $description = 'Transition donation statuses: available -> expiring_soon -> expired, based on expiry_date and per-type thresholds';

    public function handle(): int
    {
        $this->info('Starting donation status transition check...');

        $expiredCount = Donation::whereIn('status', ['available', 'expiring_soon'])
            ->where('expiry_date', '<=', now())
            ->update(['status' => 'expired']);

        $expiringSoonCount = 0;
        $rules = DonationTypeExpiryRule::all()->keyBy('donation_type');

        foreach ($rules as $donationType => $rule) {
            $expiringSoonCount += Donation::where('status', 'available')
                ->where('donation_type', $donationType)
                ->where('expiry_date', '>', now())
                ->where('expiry_date', '<=', now()->addHours($rule->expiring_soon_threshold_hours))
                ->update(['status' => 'expiring_soon']);
        }

        $this->info("Expired {$expiredCount} donation(s). Marked {$expiringSoonCount} as expiring soon.");

        Log::info('Donation status transition command completed', [
            'expired_count' => $expiredCount,
            'expiring_soon_count' => $expiringSoonCount,
        ]);

        return self::SUCCESS;
    }
}