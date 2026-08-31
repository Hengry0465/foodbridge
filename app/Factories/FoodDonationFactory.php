<?php
// Author: [Your Name]
namespace App\Factories;

use App\Models\Donation;
use App\Models\DonationTypeExpiryRule;
use Carbon\Carbon;

class FoodDonationFactory
{
    public static function create(array $data): Donation
    {
        if (empty($data['expiry_date'])) {
            $rule = DonationTypeExpiryRule::find($data['donation_type']);

            if ($rule) {
                $data['expiry_date'] = Carbon::now()
                    ->addHours($rule->default_shelf_life_hours);
            }
        }

        return Donation::create($data);
    }
}