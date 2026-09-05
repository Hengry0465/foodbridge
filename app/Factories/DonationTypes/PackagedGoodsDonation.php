<?php
namespace App\Factories\DonationTypes;

use App\Models\DonationTypeExpiryRule;
use Carbon\Carbon;

class PackagedGoodsDonation implements DonationTypeInterface
{
    public function calculateExpiry(?Carbon $providedExpiry = null): Carbon
    {
        if ($providedExpiry !== null) {
            return $providedExpiry;
        }

        $rule = DonationTypeExpiryRule::find('packaged_goods');
        $hours = $rule->default_shelf_life_hours ?? 720;

        return Carbon::now()->addHours($hours);
    }

    public function label(): string
    {
        return 'Packaged Goods';
    }
}