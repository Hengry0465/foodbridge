<?php
namespace App\Factories\DonationTypes;

use App\Models\DonationTypeExpiryRule;
use Carbon\Carbon;

class CookedFoodDonation implements DonationTypeInterface
{
    public function calculateExpiry(?Carbon $providedExpiry = null): Carbon
    {
        if ($providedExpiry !== null) {
            return $providedExpiry;
        }

        $rule = DonationTypeExpiryRule::find('cooked_food');
        $hours = $rule->default_shelf_life_hours ?? 6;

        return Carbon::now()->addHours($hours);
    }

    public function label(): string
    {
        return 'Cooked Food';
    }
}   