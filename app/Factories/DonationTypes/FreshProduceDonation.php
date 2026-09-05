<?php
namespace App\Factories\DonationTypes;

use App\Models\DonationTypeExpiryRule;
use Carbon\Carbon;

class FreshProduceDonation implements DonationTypeInterface
{
    public function calculateExpiry(?Carbon $providedExpiry = null): Carbon
    {
        if ($providedExpiry !== null) {
            return $providedExpiry;
        }

        $rule = DonationTypeExpiryRule::find('fresh_produce');
        $hours = $rule->default_shelf_life_hours ?? 72;

        return Carbon::now()->addHours($hours);
    }

    public function label(): string
    {
        return 'Fresh Produce';
    }
}
